<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (! Schema::hasColumn('departments', 'default_programme_type')) {
                    $table->string('default_programme_type', 40)->nullable()->after('contact_email');
                }
                if (! Schema::hasColumn('departments', 'supports_project')) {
                    $table->boolean('supports_project')->default(true)->after('default_programme_type');
                }
                if (! Schema::hasColumn('departments', 'supports_research')) {
                    $table->boolean('supports_research')->default(true)->after('supports_project');
                }
                if (! Schema::hasColumn('departments', 'final_year_rule_type')) {
                    $table->string('final_year_rule_type', 30)->default('PROGRAMME_DEFINED')->after('supports_research');
                }
                if (! Schema::hasColumn('departments', 'fixed_final_year')) {
                    $table->unsignedTinyInteger('fixed_final_year')->nullable()->after('final_year_rule_type');
                }
            });
        }

        if (Schema::hasTable('programmes')) {
            Schema::table('programmes', function (Blueprint $table) {
                if (! Schema::hasColumn('programmes', 'allowed_project_years')) {
                    $table->json('allowed_project_years')->nullable()->after('workflow_type');
                }
            });
        }

        if (! Schema::hasTable('academic_level_settings')) {
            Schema::create('academic_level_settings', function (Blueprint $table) {
                $table->id();
                $table->string('academic_level', 20)->unique();
                $table->unsignedTinyInteger('final_year_default')->default(3);
                $table->string('final_stage_definition', 255)->nullable();
                $table->string('workflow_complexity', 30)->default('standard');
                $table->json('output_rules')->nullable();
                $table->timestamps();
            });

            $now = now();
            foreach ($this->defaultLevelRows() as $row) {
                DB::table('academic_level_settings')->insert(array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultLevelRows(): array
    {
        return [
            [
                'academic_level' => 'diploma',
                'final_year_default' => 2,
                'final_stage_definition' => 'Final semester / year of diploma programme',
                'workflow_complexity' => 'simplified',
                'output_rules' => json_encode([
                    'default_output_type' => 'NONE',
                    'supports_project' => false,
                    'supports_research' => false,
                ]),
            ],
            [
                'academic_level' => 'certificate',
                'final_year_default' => 1,
                'final_stage_definition' => 'Certificate programme (no PRMS research or project)',
                'workflow_complexity' => 'simplified',
                'output_rules' => json_encode([
                    'default_output_type' => 'NONE',
                    'supports_project' => false,
                    'supports_research' => false,
                ]),
            ],
            [
                'academic_level' => 'bachelor',
                'final_year_default' => 3,
                'final_stage_definition' => 'Final year of bachelor programme (typically year 3 or 4)',
                'workflow_complexity' => 'standard',
                'output_rules' => json_encode([
                    'default_output_type' => 'BOTH_ALLOWED',
                    'supports_project' => true,
                    'supports_research' => true,
                ]),
            ],
            [
                'academic_level' => 'masters',
                'final_year_default' => 2,
                'final_stage_definition' => 'Final year of masters programme',
                'workflow_complexity' => 'standard',
                'output_rules' => json_encode([
                    'default_output_type' => 'RESEARCH_ONLY',
                    'supports_project' => false,
                    'supports_research' => true,
                ]),
            ],
            [
                'academic_level' => 'phd',
                'final_year_default' => 3,
                'final_stage_definition' => 'Doctoral research stage',
                'workflow_complexity' => 'extended',
                'output_rules' => json_encode([
                    'default_output_type' => 'RESEARCH_ONLY',
                    'supports_project' => false,
                    'supports_research' => true,
                ]),
            ],
        ];
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_level_settings');

        if (Schema::hasTable('programmes') && Schema::hasColumn('programmes', 'allowed_project_years')) {
            Schema::table('programmes', fn (Blueprint $table) => $table->dropColumn('allowed_project_years'));
        }

        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                foreach (['fixed_final_year', 'final_year_rule_type', 'supports_research', 'supports_project', 'default_programme_type'] as $col) {
                    if (Schema::hasColumn('departments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
