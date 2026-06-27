<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-02: Aligns the schema with the comprehensive project tracking
 * fields described in the FRD. Creates the supporting reference
 * tables (faculties, academic_years, semesters) used by the project
 * creation form, and extends `research_projects` with the metadata
 * the model already exposes (project_code, supervisors, deadlines,
 * preview/collaboration toggles, and lifecycle stage).
 *
 * The migration is defensive: each table or column is created only
 * when missing so it can be re-run safely on partially migrated
 * environments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('faculties')) {
            Schema::create('faculties', function (Blueprint $table) {
                $table->id();
                $table->string('faculty_code', 20)->unique();
                $table->string('faculty_name', 100);
                $table->foreignId('dean_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('description')->nullable();
                $table->string('office_location', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('year_name', 20)->unique();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('semesters')) {
            Schema::create('semesters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->string('semester_name', 50);
                $table->unsignedTinyInteger('semester_number')->default(1);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('departments') && !Schema::hasColumn('departments', 'faculty_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('faculty_id')->nullable()->after('id')
                    ->constrained('faculties')->nullOnDelete();
            });
        }

        Schema::table('research_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('research_projects', 'project_code')) {
                $table->string('project_code', 30)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('research_projects', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('student_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'co_supervisor_id')) {
                $table->foreignId('co_supervisor_id')->nullable()->after('supervisor_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('project_group_id')
                    ->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'faculty_id')) {
                $table->foreignId('faculty_id')->nullable()->after('department_id')
                    ->constrained('faculties')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('faculty_id')
                    ->constrained('programmes')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('program_id')
                    ->constrained('academic_years')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'semester_id')) {
                $table->foreignId('semester_id')->nullable()->after('academic_year_id')
                    ->constrained('semesters')->nullOnDelete();
            }
            if (!Schema::hasColumn('research_projects', 'project_type')) {
                $table->string('project_type', 50)->nullable()->after('project_type_id');
            }
            if (!Schema::hasColumn('research_projects', 'keywords')) {
                $table->string('keywords', 500)->nullable()->after('abstract');
            }
            if (!Schema::hasColumn('research_projects', 'research_area')) {
                $table->string('research_area', 200)->nullable()->after('keywords');
            }
            if (!Schema::hasColumn('research_projects', 'funding_source')) {
                $table->string('funding_source', 200)->nullable()->after('research_area');
            }
            if (!Schema::hasColumn('research_projects', 'ethical_clearance_number')) {
                $table->string('ethical_clearance_number', 100)->nullable()
                    ->after('funding_source');
            }
            if (!Schema::hasColumn('research_projects', 'current_stage')) {
                $table->string('current_stage', 40)->default('Draft')->after('status');
            }
            if (!Schema::hasColumn('research_projects', 'submission_deadline')) {
                $table->dateTime('submission_deadline')->nullable()->after('current_stage');
            }
            if (!Schema::hasColumn('research_projects', 'plagiarism_score')) {
                $table->decimal('plagiarism_score', 5, 2)->nullable()->after('submission_deadline');
            }
            if (!Schema::hasColumn('research_projects', 'preview_enabled')) {
                $table->boolean('preview_enabled')->default(true)->after('plagiarism_score');
            }
            if (!Schema::hasColumn('research_projects', 'collaboration_enabled')) {
                $table->boolean('collaboration_enabled')->default(true)->after('preview_enabled');
            }
            if (!Schema::hasColumn('research_projects', 'final_grade')) {
                $table->decimal('final_grade', 5, 2)->nullable()->after('collaboration_enabled');
            }
            if (!Schema::hasColumn('research_projects', 'final_grade_letter')) {
                $table->string('final_grade_letter', 5)->nullable()->after('final_grade');
            }
            if (!Schema::hasColumn('research_projects', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('final_grade_letter');
            }
            if (!Schema::hasColumn('research_projects', 'archived_date')) {
                $table->dateTime('archived_date')->nullable()->after('is_archived');
            }
        });
    }

    public function down(): void
    {
        Schema::table('research_projects', function (Blueprint $table) {
            $columns = [
                'project_code',
                'supervisor_id',
                'co_supervisor_id',
                'department_id',
                'faculty_id',
                'program_id',
                'academic_year_id',
                'semester_id',
                'project_type',
                'keywords',
                'research_area',
                'funding_source',
                'ethical_clearance_number',
                'current_stage',
                'submission_deadline',
                'plagiarism_score',
                'preview_enabled',
                'collaboration_enabled',
                'final_grade',
                'final_grade_letter',
                'is_archived',
                'archived_date',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('research_projects', $column)) {
                    if (in_array($column, ['supervisor_id', 'co_supervisor_id', 'department_id', 'faculty_id', 'program_id', 'academic_year_id', 'semester_id'], true)) {
                        try { $table->dropForeign([$column]); } catch (\Throwable $e) { /* ignore */ }
                    }
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'faculty_id')) {
            Schema::table('departments', function (Blueprint $table) {
                try { $table->dropForeign(['faculty_id']); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('faculty_id');
            });
        }

        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('faculties');
    }
};
