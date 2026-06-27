<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-04: Allow a rubric-based evaluation to be tied directly to the
 * submission it was scored against (supervisor review queue).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('student_evaluations', 'project_submission_id')) {
            Schema::table('student_evaluations', function (Blueprint $table) {
                $table->foreignId('project_submission_id')->nullable()
                    ->after('project_group_id')
                    ->constrained('project_submissions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_evaluations', 'project_submission_id')) {
            Schema::table('student_evaluations', function (Blueprint $table) {
                try { $table->dropForeign(['project_submission_id']); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('project_submission_id');
            });
        }
    }
};
