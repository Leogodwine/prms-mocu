<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('programmes')) {
            Schema::table('programmes', function (Blueprint $table) {
                if (! Schema::hasColumn('programmes', 'academic_level')) {
                    $table->string('academic_level', 20)->default('bachelor')->after('duration_years');
                }
                if (! Schema::hasColumn('programmes', 'final_year')) {
                    $table->unsignedTinyInteger('final_year')->nullable()->after('academic_level');
                }
                if (! Schema::hasColumn('programmes', 'output_type')) {
                    $table->string('output_type', 30)->default('RESEARCH_ONLY')->after('final_year');
                }
                if (! Schema::hasColumn('programmes', 'workflow_type')) {
                    $table->string('workflow_type', 30)->default('standard')->after('output_type');
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (! Schema::hasColumn('students', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('programme_id')
                        ->constrained('departments')->nullOnDelete();
                }
                if (! Schema::hasColumn('students', 'academic_level')) {
                    $table->string('academic_level', 20)->nullable()->after('department_id');
                }
                if (! Schema::hasColumn('students', 'workflow_role')) {
                    $table->string('workflow_role', 40)->default('VIEWER_ONLY')->after('academic_level');
                }
                if (! Schema::hasColumn('students', 'output_track')) {
                    $table->string('output_track', 20)->nullable()->after('workflow_role');
                }
            });
        }

        if (! Schema::hasTable('department_workflow_rules')) {
            Schema::create('department_workflow_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->string('academic_level', 20);
                $table->unsignedTinyInteger('final_year')->nullable();
                $table->string('output_type', 30)->nullable();
                $table->string('workflow_type', 30)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['department_id', 'academic_level'], 'dept_workflow_level_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_workflow_rules');

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                foreach (['output_track', 'workflow_role', 'academic_level', 'department_id'] as $column) {
                    if (Schema::hasColumn('students', $column)) {
                        if ($column === 'department_id') {
                            $table->dropConstrainedForeignId('department_id');
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });
        }

        if (Schema::hasTable('programmes')) {
            Schema::table('programmes', function (Blueprint $table) {
                foreach (['workflow_type', 'output_type', 'final_year', 'academic_level'] as $column) {
                    if (Schema::hasColumn('programmes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
