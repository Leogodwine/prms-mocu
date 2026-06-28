<?php

namespace App\Support;

use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps the optional `students` profile row aligned with academic fields on `users`
 * when staff update department / programme / year of study.
 */
class StudentAcademicRecordSync
{
    public static function syncLinkedStudentRowFromUser(User $user): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        $profile = $user->studentProfile;
        if ($profile === null) {
            return;
        }

        $dirty = false;

        if (Schema::hasColumn('students', 'registration_number')) {
            $profile->registration_number = $user->login_id;
            $dirty = true;
        }

        if (Schema::hasColumn('students', 'full_name')) {
            $profile->full_name = $user->name;
            $dirty = true;
        }

        if (Schema::hasColumn('students', 'university_email')) {
            $profile->university_email = $user->email;
            $dirty = true;
        }

        if (Schema::hasColumn('students', 'year_of_study') && $user->year_of_study !== null) {
            $profile->year_of_study = (int) $user->year_of_study;
            $dirty = true;
        }

        if (Schema::hasColumn('students', 'programme_id') && filled($user->programme)) {
            $needle = mb_strtolower(trim((string) $user->programme));
            $prog = Program::query()
                ->whereRaw('LOWER(programme_code) = ?', [$needle])
                ->orWhereRaw('LOWER(programme_name) = ?', [$needle])
                ->first();
            if ($prog !== null) {
                $profile->programme_id = $prog->id;
                $dirty = true;
            }
        }

        if ($dirty) {
            $profile->save();
        }

        self::syncExpectedGraduationFromUser($user);
    }

    public static function computeExpectedGraduationYear(User $user): ?int
    {
        if ($user->year_of_study === null || (int) $user->year_of_study < 1) {
            return null;
        }

        $programme = FinalYearWorkflowEngine::resolveProgramme($user);
        if ($programme === null || $programme->duration_years === null || (int) $programme->duration_years < 1) {
            return null;
        }

        $durationYears = (int) $programme->duration_years;
        $yearOfStudy = (int) $user->year_of_study;

        if ($yearOfStudy > $durationYears) {
            return null;
        }

        $yearsRemaining = $durationYears - $yearOfStudy;

        return (int) now()->year + max(0, $yearsRemaining);
    }

    public static function syncExpectedGraduationFromUser(User $user): void
    {
        $year = self::computeExpectedGraduationYear($user);
        if ($year === null || $year < 2000 || ! Schema::hasTable('students')) {
            return;
        }

        $profile = $user->studentProfile;
        if ($profile === null) {
            return;
        }

        if (Schema::hasColumn('students', 'expected_graduation')) {
            $profile->expected_graduation = sprintf('%d-12-31', $year);
            $profile->save();
        }
    }
}
