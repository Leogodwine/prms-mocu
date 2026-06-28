<?php

namespace App\Support;

use App\Enums\AcademicLevel;
use App\Enums\OutputTrack;
use App\Enums\ProgramOutputType;
use App\Enums\StudentWorkflowRole;
use App\Enums\FinalYearRuleType;
use App\Enums\WorkflowType;
use App\Models\AcademicLevelSetting;
use App\Models\Department;
use App\Models\DepartmentWorkflowRule;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Rule engine for final-year eligibility, workflow roles, and research vs project tracks.
 *
 * Resolution order: student profile → department rule → programme rule → config defaults.
 */
final class FinalYearWorkflowEngine
{
    public static function resolveStudentProfile(User $user): ?Student
    {
        if (! Schema::hasTable('students')) {
            return null;
        }

        if ($user->relationLoaded('studentProfile')) {
            return $user->studentProfile;
        }

        return $user->studentProfile()->with(['programme.department', 'department'])->first();
    }

    public static function resolveProgramme(User $user): ?Program
    {
        $profile = self::resolveStudentProfile($user);

        if ($profile?->programme instanceof Program) {
            return $profile->programme;
        }

        $programmeName = trim((string) ($user->programme ?? ''));
        if ($programmeName === '' || ! Schema::hasTable('programmes')) {
            return null;
        }

        return Program::query()
            ->with('department')
            ->where(function ($query) use ($programmeName) {
                $query->whereRaw('LOWER(programme_code) = ?', [strtolower($programmeName)])
                    ->orWhereRaw('LOWER(programme_name) = ?', [strtolower($programmeName)])
                    ->orWhere('programme_name', 'like', '%'.$programmeName.'%');
            })
            ->first();
    }

    public static function resolveDepartment(User $user): ?Department
    {
        $profile = self::resolveStudentProfile($user);

        if ($profile?->department instanceof Department) {
            return $profile->department;
        }

        $programme = self::resolveProgramme($user);

        return $programme?->department;
    }

    public static function academicLevel(User $user): AcademicLevel
    {
        $profile = self::resolveStudentProfile($user);

        if ($profile !== null && filled($profile->academic_level)) {
            return AcademicLevel::tryFromMixed($profile->academic_level);
        }

        $programme = self::resolveProgramme($user);

        if ($programme !== null && filled($programme->academic_level)) {
            return AcademicLevel::tryFromMixed($programme->academic_level);
        }

        return AcademicLevel::tryFromMixed(WorkflowSettingsCatalog::defaultAcademicLevel());
    }

    public static function yearOfStudy(User $user): ?int
    {
        $profile = self::resolveStudentProfile($user);

        if ($profile?->year_of_study !== null) {
            return (int) $profile->year_of_study;
        }

        if ($user->year_of_study !== null) {
            return (int) $user->year_of_study;
        }

        return null;
    }

    public static function enrollmentStatus(User $user): string
    {
        $profile = self::resolveStudentProfile($user);
        $status = strtolower(trim((string) ($profile?->enrollment_status ?? $user->enrollment_status ?? 'active')));

        return in_array($status, ['active', 'suspended', 'graduated', 'withdrawn'], true)
            ? $status
            : 'active';
    }

    public static function resolveDepartmentRule(User $user): ?DepartmentWorkflowRule
    {
        if (! Schema::hasTable('department_workflow_rules')) {
            return null;
        }

        $department = self::resolveDepartment($user);
        if ($department === null) {
            return null;
        }

        $level = self::academicLevel($user)->value;

        return DepartmentWorkflowRule::query()
            ->where('department_id', $department->id)
            ->where('academic_level', $level)
            ->where('is_active', true)
            ->first();
    }

    public static function resolveLevelSetting(User $user): ?AcademicLevelSetting
    {
        if (! Schema::hasTable('academic_level_settings')) {
            return null;
        }

        $level = self::academicLevel($user)->value;

        return AcademicLevelSetting::query()->where('academic_level', $level)->first();
    }

    public static function resolveFinalYear(User $user): int
    {
        $department = self::resolveDepartment($user);
        $levelSetting = self::resolveLevelSetting($user);

        if ($department !== null) {
            $ruleType = $department->finalYearRuleTypeEnum();

            if ($ruleType === FinalYearRuleType::FixedYear && $department->fixed_final_year !== null) {
                return (int) $department->fixed_final_year;
            }

            if ($ruleType === FinalYearRuleType::LevelBased) {
                $departmentRule = self::resolveDepartmentRule($user);
                if ($departmentRule?->final_year !== null) {
                    return (int) $departmentRule->final_year;
                }
                if ($levelSetting !== null) {
                    return (int) $levelSetting->final_year_default;
                }
            }
        }

        $departmentRule = self::resolveDepartmentRule($user);
        if ($departmentRule?->final_year !== null) {
            return (int) $departmentRule->final_year;
        }

        $programme = self::resolveProgramme($user);

        if ($programme !== null) {
            if ($programme->final_year !== null) {
                return (int) $programme->final_year;
            }

            if ($programme->project_year !== null) {
                return (int) $programme->project_year;
            }

            if ($programme->duration_years !== null) {
                return (int) $programme->duration_years;
            }
        }

        if ($levelSetting !== null) {
            return (int) $levelSetting->final_year_default;
        }

        return WorkflowSettingsCatalog::defaultFinalYearForLevel(self::academicLevel($user));
    }

    public static function resolveOutputType(User $user): ProgramOutputType
    {
        $department = self::resolveDepartment($user);
        $departmentRule = self::resolveDepartmentRule($user);
        $levelSetting = self::resolveLevelSetting($user);
        $programme = self::resolveProgramme($user);

        $outputType = null;

        if ($departmentRule?->output_type) {
            $outputType = ProgramOutputType::tryFromMixed($departmentRule->output_type);
        } elseif ($programme !== null && filled($programme->output_type)) {
            $outputType = ProgramOutputType::tryFromMixed($programme->output_type);
        } elseif ($levelSetting !== null) {
            $outputType = ProgramOutputType::tryFromMixed($levelSetting->defaultOutputType());
        } elseif ($programme !== null && $programme->is_project_eligible) {
            $outputType = ProgramOutputType::BothAllowed;
        } else {
            $outputType = ProgramOutputType::ResearchOnly;
        }

        if ($department !== null) {
            if (! $department->supports_project && $outputType !== ProgramOutputType::ResearchOnly) {
                $outputType = ProgramOutputType::ResearchOnly;
            }
            if (! $department->supports_research && $outputType !== ProgramOutputType::ProjectOnly) {
                $outputType = ProgramOutputType::ProjectOnly;
            }
        }

        if ($levelSetting !== null) {
            if (! $levelSetting->supportsProject() && $outputType === ProgramOutputType::BothAllowed) {
                $outputType = ProgramOutputType::ResearchOnly;
            }
            if (! $levelSetting->supportsResearch() && $outputType === ProgramOutputType::BothAllowed) {
                $outputType = ProgramOutputType::ProjectOnly;
            }
        }

        $year = self::yearOfStudy($user);
        if ($programme !== null && $year !== null) {
            $allowed = $programme->allowedProjectYearsList();
            if ($allowed !== [] && ! in_array($year, $allowed, true) && $outputType === ProgramOutputType::BothAllowed) {
                $outputType = ProgramOutputType::ResearchOnly;
            }
            if ($allowed !== [] && ! in_array($year, $allowed, true) && $outputType === ProgramOutputType::ProjectOnly) {
                $outputType = ProgramOutputType::ResearchOnly;
            }
        }

        return $outputType;
    }

    public static function resolveWorkflowType(User $user): string
    {
        $departmentRule = self::resolveDepartmentRule($user);
        if ($departmentRule?->workflow_type) {
            return WorkflowType::tryFromMixed($departmentRule->workflow_type)->value;
        }

        $programme = self::resolveProgramme($user);

        if ($programme !== null && filled($programme->workflow_type)) {
            return WorkflowType::tryFromMixed($programme->workflow_type)->value;
        }

        return WorkflowType::tryFromMixed(WorkflowSettingsCatalog::defaultWorkflowType())->value;
    }

    public static function isFinalYearEligible(User $user): bool
    {
        if (! $user->isStudentUser()) {
            return false;
        }

        if (self::enrollmentStatus($user) !== 'active') {
            return false;
        }

        if ($user->account_status !== 'active') {
            return false;
        }

        $year = self::yearOfStudy($user);
        if ($year === null) {
            return false;
        }

        return $year >= self::resolveFinalYear($user);
    }

    public static function resolveOutputTrack(User $user): ?OutputTrack
    {
        $profile = self::resolveStudentProfile($user);

        if ($profile !== null && filled($profile->output_track)) {
            return OutputTrack::tryFromMixed($profile->output_track);
        }

        $outputType = self::resolveOutputType($user);

        return match ($outputType) {
            ProgramOutputType::ResearchOnly => OutputTrack::Research,
            ProgramOutputType::ProjectOnly => OutputTrack::Project,
            ProgramOutputType::BothAllowed => null,
        };
    }

    public static function determineWorkflowRole(User $user): StudentWorkflowRole
    {
        if (! $user->isStudentUser()) {
            return StudentWorkflowRole::NoAccess;
        }

        $enrollment = self::enrollmentStatus($user);

        if (in_array($enrollment, ['graduated', 'withdrawn'], true)) {
            return StudentWorkflowRole::NoAccess;
        }

        if ($enrollment === 'suspended' || $user->account_status !== 'active') {
            return StudentWorkflowRole::NoAccess;
        }

        if (! self::isFinalYearEligible($user)) {
            return StudentWorkflowRole::ViewerOnly;
        }

        $outputTrack = self::resolveOutputTrack($user);

        if ($outputTrack === OutputTrack::Research) {
            return StudentWorkflowRole::ResearchCandidate;
        }

        if ($outputTrack === OutputTrack::Project) {
            return StudentWorkflowRole::ProjectCandidate;
        }

        return match (self::resolveOutputType($user)) {
            ProgramOutputType::ResearchOnly => StudentWorkflowRole::ResearchCandidate,
            ProgramOutputType::ProjectOnly => StudentWorkflowRole::ProjectCandidate,
            ProgramOutputType::BothAllowed => StudentWorkflowRole::FinalYearStudent,
        };
    }

    /**
     * @return list<string>
     */
    public static function availableTracks(User $user): array
    {
        $workflowRole = self::determineWorkflowRole($user);

        if (! $workflowRole->canEnterWorkflow()) {
            return [];
        }

        $tracks = ['proposal'];
        $outputTrack = self::resolveOutputTrack($user);
        $outputType = self::resolveOutputType($user);

        if ($workflowRole === StudentWorkflowRole::ResearchCandidate || $outputTrack === OutputTrack::Research) {
            $tracks[] = 'research';
        }

        if ($workflowRole === StudentWorkflowRole::ProjectCandidate || $outputTrack === OutputTrack::Project) {
            $tracks[] = 'project';
        }

        if ($workflowRole === StudentWorkflowRole::FinalYearStudent && $outputType === ProgramOutputType::BothAllowed) {
            $tracks[] = 'research';
            $tracks[] = 'project';
        }

        return array_values(array_unique($tracks));
    }

    public static function hasTrack(User $user, string $track): bool
    {
        return in_array(strtolower(trim($track)), self::availableTracks($user), true);
    }

    public static function workflowBlockReason(User $user): ?string
    {
        $role = self::determineWorkflowRole($user);

        if ($role === StudentWorkflowRole::NoAccess) {
            $enrollment = self::enrollmentStatus($user);

            if ($enrollment === 'graduated') {
                return 'Your student record is marked as graduated. Research workflow access is closed.';
            }

            if ($enrollment === 'suspended' || $user->account_status !== 'active') {
                return 'Your account is not active. Contact the coordinator before starting research work.';
            }

            return 'You do not have access to the research workflow.';
        }

        if ($role === StudentWorkflowRole::ViewerOnly) {
            $programme = self::resolveProgramme($user);
            $expectedYear = self::resolveFinalYear($user);
            $currentYear = self::yearOfStudy($user);
            $programmeLabel = $programme?->programme_name ?? 'your programme';

            if ($currentYear === null) {
                return 'Your year of study is not set. Contact the coordinator to update your profile before starting research work.';
            }

            return "Final-year research and project work for {$programmeLabel} begins in year {$expectedYear}. You are currently in year {$currentYear}.";
        }

        return null;
    }

    public static function mustStartWithProposal(User $user): bool
    {
        return self::determineWorkflowRole($user)->canEnterWorkflow();
    }

    public static function proposalIsApproved(Collection $latestByStage): bool
    {
        return ($latestByStage->get('Complete Proposal Document')?->status ?? '') === 'approved';
    }

    /**
     * Blocks execution-track uploads until the proposal stage is complete and approved.
     */
    public static function executionTrackBlockReason(User $user, string $stageName, Collection $latestByStage): ?string
    {
        if (! self::mustStartWithProposal($user)) {
            return null;
        }

        $track = StudentStageProgress::workTypeFromStage($stageName);
        if (! in_array($track, ['research', 'project'], true)) {
            return null;
        }

        if (self::proposalIsApproved($latestByStage)) {
            return null;
        }

        return 'All final-year students must complete and receive approval for the Complete Proposal Document before starting '.$track.' execution work.';
    }

    /**
     * @return array<string, mixed>
     */
    public static function academicContext(User $user): array
    {
        $programme = self::resolveProgramme($user);
        $workflowRole = self::determineWorkflowRole($user);

        return [
            'programme' => $programme,
            'department' => self::resolveDepartment($user),
            'year_of_study' => self::yearOfStudy($user),
            'academic_level' => self::academicLevel($user),
            'final_year' => self::resolveFinalYear($user),
            'research_year' => self::resolveFinalYear($user),
            'in_research_year' => self::isFinalYearEligible($user),
            'in_final_year' => self::isFinalYearEligible($user),
            'output_type' => self::resolveOutputType($user),
            'output_track' => self::resolveOutputTrack($user),
            'workflow_type' => self::resolveWorkflowType($user),
            'workflow_role' => $workflowRole,
            'includes_project_track' => self::hasTrack($user, 'project'),
            'includes_research_track' => self::hasTrack($user, 'research'),
            'available_tracks' => self::availableTracks($user),
            'research_year_block' => self::workflowBlockReason($user),
            'workflow_block' => self::workflowBlockReason($user),
        ];
    }

    /**
     * Full eligibility evaluation for admin preview and diagnostics.
     *
     * @return array<string, mixed>
     */
    public static function evaluateEligibility(User $user): array
    {
        $context = self::academicContext($user);
        $workflowType = WorkflowType::tryFromMixed($context['workflow_type'] ?? '');

        return array_merge($context, [
            'student_name' => $user->name,
            'student_email' => $user->email,
            'login_id' => $user->login_id,
            'enrollment_status' => self::enrollmentStatus($user),
            'account_status' => $user->account_status,
            'mapped_user_role' => $user->role,
            'workflow_type_label' => $workflowType->label(),
            'workflow_stages' => $workflowType->stages(),
            'output_type_label' => $context['output_type']->label(),
            'workflow_role_label' => $context['workflow_role']->label(),
            'rule_trace' => self::buildRuleTrace($user),
        ]);
    }

    /**
     * @return list<array{source: string, detail: string}>
     */
    public static function buildRuleTrace(User $user): array
    {
        $trace = [];
        $department = self::resolveDepartment($user);
        $programme = self::resolveProgramme($user);
        $levelSetting = self::resolveLevelSetting($user);
        $departmentRule = self::resolveDepartmentRule($user);

        if ($department) {
            $trace[] = [
                'source' => 'Department',
                'detail' => $department->department_name.' — rule: '.$department->finalYearRuleTypeEnum()->label()
                    .'; project: '.($department->supports_project ? 'yes' : 'no')
                    .'; research: '.($department->supports_research ? 'yes' : 'no'),
            ];
        }

        if ($departmentRule) {
            $trace[] = [
                'source' => 'Department level override',
                'detail' => 'Final year '.$departmentRule->final_year.', output '.$departmentRule->output_type,
            ];
        }

        if ($programme) {
            $years = $programme->allowedProjectYearsList();
            $trace[] = [
                'source' => 'Programme',
                'detail' => $programme->programme_code.' — final year '.($programme->final_year ?? '—')
                    .', output '.($programme->output_type ?? '—')
                    .($years !== [] ? ', project years ['.implode(',', $years).']' : ''),
            ];
        }

        if ($levelSetting) {
            $trace[] = [
                'source' => 'Academic level',
                'detail' => $levelSetting->levelEnum()->label().' — default final year '.$levelSetting->final_year_default
                    .', complexity '.$levelSetting->workflow_complexity,
            ];
        }

        $trace[] = [
            'source' => 'Computed',
            'detail' => 'Final year '.self::resolveFinalYear($user)
                .'; eligible: '.(self::isFinalYearEligible($user) ? 'yes' : 'no')
                .'; role '.self::determineWorkflowRole($user)->value,
        ];

        return $trace;
    }
}
