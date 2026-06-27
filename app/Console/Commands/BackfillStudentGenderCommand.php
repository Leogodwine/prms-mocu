<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Support\StudentGenderNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillStudentGenderCommand extends Command
{
    protected $signature = 'prms:backfill-student-gender
                            {--dry-run : Show what would change without saving}';

    protected $description = 'Copy gender from sis_data into students.gender for existing records';

    public function handle(): int
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'gender')) {
            $this->error('The students.gender column is missing. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;
        $stillMissing = 0;

        $query = Student::query()->orderBy('id');

        foreach ($query->cursor() as $student) {
            if (filled($student->gender)) {
                $skipped++;

                continue;
            }

            $fromSis = StudentGenderNormalizer::normalize(
                data_get($student->sis_data, 'gender') ?? data_get($student->sis_data, 'sex')
            );

            if ($fromSis === null) {
                $stillMissing++;

                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] {$student->registration_number} — {$student->full_name} → {$fromSis}");
                $updated++;

                continue;
            }

            $student->gender = $fromSis;
            $student->save();
            $updated++;
        }

        if ($dryRun) {
            $this->info("Dry run complete. {$updated} student(s) would receive gender from sis_data. {$skipped} already set. {$stillMissing} still missing gender in SIS data.");
        } else {
            $this->info("Updated gender for {$updated} student(s). {$skipped} already had gender. {$stillMissing} still have no gender source in sis_data.");
        }

        return self::SUCCESS;
    }
}
