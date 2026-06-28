<?php

namespace App\Support;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures a `students` row exists for student-role users so coordinator
 * dashboards and other flows that query `Student` see them.
 */
final class StudentProfileProvisioner
{
    public static function resolveProgrammeIdFromLabel(?string $label): ?int
    {
        if ($label === null || trim($label) === '') {
            return null;
        }
        if (! Schema::hasTable('programmes')) {
            return null;
        }

        $needle = mb_strtolower(trim($label));

        $prog = Program::query()
            ->whereRaw('LOWER(programme_code) = ?', [$needle])
            ->orWhereRaw('LOWER(programme_name) = ?', [$needle])
            ->first();

        return $prog?->id;
    }

    /**
     * Create a `students` row when missing. Returns true if a new row was inserted.
     */
    public static function ensureStudentProfile(User $user): bool
    {
        if (! Schema::hasTable('students') || ! $user->isStudentUser()) {
            return false;
        }

        $studyYear = (int) ($user->year_of_study ?? 1);
        if ($studyYear < 1 || $studyYear > 8) {
            $studyYear = 1;
        }

        $student = Student::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'registration_number' => $user->login_id,
                'full_name' => $user->name,
                'programme_id' => self::resolveProgrammeIdFromLabel($user->programme),
                'year_of_study' => $studyYear,
                'enrollment_status' => 'active',
                'university_email' => $user->email,
            ]
        );

        StudentWorkflowAssigner::syncForUser($user->fresh());

        return $student->wasRecentlyCreated;
    }
}
