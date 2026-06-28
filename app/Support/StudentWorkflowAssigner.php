<?php

namespace App\Support;

use App\Enums\OutputTrack;
use App\Enums\ProgramOutputType;
use App\Enums\StudentWorkflowRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Persists workflow role / track on student profiles and maps to users.role for routing.
 */
final class StudentWorkflowAssigner
{
    public static function syncForUser(User $user, bool $persistUserRole = true): StudentWorkflowRole
    {
        if (! $user->isStudentUser() || ! Schema::hasTable('students')) {
            return StudentWorkflowRole::NoAccess;
        }

        if (! $user->relationLoaded('studentProfile')) {
            $user->load('studentProfile.programme.department');
        }

        if ($user->studentProfile === null) {
            StudentProfileProvisioner::ensureStudentProfile($user);
            $user = $user->fresh(['studentProfile.programme.department']);
        }

        $profile = $user->studentProfile;
        if (! $profile instanceof Student) {
            $profile = Student::query()->firstOrNew(['user_id' => $user->id]);
        }

        $programme = FinalYearWorkflowEngine::resolveProgramme($user);

        if ($profile->department_id === null && $programme?->department_id !== null) {
            $profile->department_id = $programme->department_id;
        }

        if (! filled($profile->academic_level)) {
            $profile->academic_level = FinalYearWorkflowEngine::academicLevel($user)->value;
        }

        if ($profile->programme_id === null && $programme !== null) {
            $profile->programme_id = $programme->id;
        }

        $workflowRole = FinalYearWorkflowEngine::determineWorkflowRole($user);
        $profile->workflow_role = $workflowRole->value;
        $profile->save();

        if ($persistUserRole) {
            self::syncUserRoleFromWorkflow($user, $workflowRole);
        }

        return $workflowRole;
    }

    public static function assignOutputTrack(User $user, OutputTrack $track): void
    {
        if (! Schema::hasTable('students') || ! $user->isStudentUser()) {
            return;
        }

        $profile = Student::query()->firstOrNew(['user_id' => $user->id]);
        $profile->output_track = $track->value;
        $profile->save();

        self::syncForUser($user->fresh());
    }

    public static function assignOutputTrackFromProjectType(User $user, ?string $projectType): void
    {
        if ($projectType === null || trim($projectType) === '') {
            return;
        }

        $normalized = strtolower(trim($projectType));

        $track = match (true) {
            in_array($normalized, ['research', 'dissertation', 'thesis', 'research paper'], true) => OutputTrack::Research,
            in_array($normalized, ['project', 'project report', 'system', 'product'], true) => OutputTrack::Project,
            default => null,
        };

        if ($track === null) {
            return;
        }

        $outputType = FinalYearWorkflowEngine::resolveOutputType($user);
        $allowed = match ($outputType) {
            ProgramOutputType::BothAllowed => true,
            ProgramOutputType::ResearchOnly => $track === OutputTrack::Research,
            ProgramOutputType::ProjectOnly => $track === OutputTrack::Project,
        };

        if ($allowed) {
            self::assignOutputTrack($user, $track);
        }
    }

    public static function syncUserRoleFromWorkflow(User $user, ?StudentWorkflowRole $workflowRole = null): void
    {
        $workflowRole ??= FinalYearWorkflowEngine::determineWorkflowRole($user);

        $mappedRole = match ($workflowRole) {
            StudentWorkflowRole::ResearchCandidate => 'research_student',
            StudentWorkflowRole::ProjectCandidate => 'project_student',
            StudentWorkflowRole::FinalYearStudent => 'normal_student',
            StudentWorkflowRole::ViewerOnly => 'normal_student',
            StudentWorkflowRole::NoAccess => $user->role,
        };

        if ($user->role !== $mappedRole && in_array($mappedRole, ['research_student', 'project_student', 'normal_student'], true)) {
            $user->role = $mappedRole;
            $user->save();
        }
    }

    public static function reevaluateAll(): int
    {
        if (! Schema::hasTable('students')) {
            return 0;
        }

        $count = 0;

        User::query()
            ->whereIn('role', ['project_student', 'research_student', 'normal_student', 'student'])
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    self::syncForUser($user);
                    $count++;
                }
            });

        return $count;
    }
}
