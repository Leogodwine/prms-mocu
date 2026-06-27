<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\StudentAcademicRecordSync;
use App\Support\StudentProfileProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillStudentProfilesCommand extends Command
{
    protected $signature = 'prms:backfill-student-profiles
                            {--dry-run : List users that would get a profile without saving}';

    protected $description = 'Create missing `students` rows for users with student roles (coordinator visibility)';

    public function handle(): int
    {
        if (! Schema::hasTable('students')) {
            $this->error('Table `students` does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $query = User::query()
            ->whereIn('role', [
                'project_student',
                'research_student',
                'normal_student',
                'student',
            ])
            ->whereDoesntHave('studentProfile');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No student users are missing a `students` profile.');

            return self::SUCCESS;
        }

        $this->info("Found {$total} user(s) without a student profile.");

        $created = 0;
        $errors = 0;

        foreach ($query->cursor() as $user) {
            if ($dryRun) {
                $this->line("[dry-run] Would provision: {$user->id} — {$user->name} ({$user->login_id})");
                $created++;

                continue;
            }

            try {
                if (StudentProfileProvisioner::ensureStudentProfile($user)) {
                    $created++;
                }
                $user->refresh();
                StudentAcademicRecordSync::syncLinkedStudentRowFromUser($user);
            } catch (\Throwable $e) {
                $errors++;
                $this->error("User {$user->id} ({$user->email}): {$e->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$created} profile(s) would be created. Run without --dry-run to apply.");
        } else {
            $this->info("Created {$created} new student profile(s).");
            if ($errors > 0) {
                $this->warn("{$errors} row(s) failed; see messages above.");
            }
        }

        return $errors > 0 && ! $dryRun ? self::FAILURE : self::SUCCESS;
    }
}
