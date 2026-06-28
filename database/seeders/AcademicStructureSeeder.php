<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentWorkflowRule;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Academic departments, programmes, and final-year workflow rules.
 *
 * Safe to run in production after migrate:
 *   php artisan db:seed --class=AcademicStructureSeeder --force
 */
class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('departments') || ! Schema::hasTable('programmes')) {
            $this->command?->warn('AcademicStructureSeeder: departments/programmes tables missing — skipped.');

            return;
        }

        $structures = [
            [
                'department' => [
                    'department_code' => 'CICT',
                    'department_name' => 'Computing and Information Technology',
                    'head_of_department' => null,
                    'contact_email' => 'cict@mocu.ac.tz',
                ],
                'programmes' => [
                    [
                        'programme_code' => 'BBICT',
                        'programme_name' => 'Bachelor of Business Information and Communication Technology',
                        'duration_years' => 3,
                        'academic_level' => 'bachelor',
                        'final_year' => 3,
                        'output_type' => 'BOTH_ALLOWED',
                        'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
                        'is_project_eligible' => true,
                        'project_year' => 3,
                    ],
                    [
                        'programme_code' => 'DBICT',
                        'programme_name' => 'Diploma in Business Information and Communication Technology',
                        'duration_years' => 2,
                        'academic_level' => 'diploma',
                        'final_year' => 2,
                        'output_type' => 'PROJECT_ONLY',
                        'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
                        'is_project_eligible' => true,
                        'project_year' => 2,
                    ],
                ],
                'rules' => [
                    ['academic_level' => 'bachelor', 'final_year' => 3, 'output_type' => 'BOTH_ALLOWED'],
                    ['academic_level' => 'diploma', 'final_year' => 2, 'output_type' => 'PROJECT_ONLY'],
                ],
            ],
            [
                'department' => [
                    'department_code' => 'EDU',
                    'department_name' => 'Education',
                    'contact_email' => 'education@mocu.ac.tz',
                ],
                'programmes' => [
                    [
                        'programme_code' => 'BED',
                        'programme_name' => 'Bachelor of Education',
                        'duration_years' => 3,
                        'academic_level' => 'bachelor',
                        'final_year' => 3,
                        'output_type' => 'RESEARCH_ONLY',
                        'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
                        'is_project_eligible' => false,
                        'project_year' => 3,
                    ],
                ],
                'rules' => [
                    ['academic_level' => 'bachelor', 'final_year' => 3, 'output_type' => 'RESEARCH_ONLY'],
                ],
            ],
            [
                'department' => [
                    'department_code' => 'BUS',
                    'department_name' => 'Business and Management Studies',
                    'contact_email' => 'business@mocu.ac.tz',
                ],
                'programmes' => [
                    [
                        'programme_code' => 'BBA',
                        'programme_name' => 'Bachelor of Business Administration',
                        'duration_years' => 3,
                        'academic_level' => 'bachelor',
                        'final_year' => 3,
                        'output_type' => 'RESEARCH_ONLY',
                        'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
                        'is_project_eligible' => false,
                        'project_year' => 3,
                    ],
                    [
                        'programme_code' => 'MBA',
                        'programme_name' => 'Master of Business Administration',
                        'duration_years' => 2,
                        'academic_level' => 'masters',
                        'final_year' => 2,
                        'output_type' => 'RESEARCH_ONLY',
                        'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
                        'is_project_eligible' => false,
                        'project_year' => 2,
                    ],
                ],
                'rules' => [
                    ['academic_level' => 'bachelor', 'final_year' => 3, 'output_type' => 'RESEARCH_ONLY'],
                    ['academic_level' => 'masters', 'final_year' => 2, 'output_type' => 'RESEARCH_ONLY'],
                ],
            ],
        ];

        foreach ($structures as $entry) {
            $department = Department::query()->updateOrCreate(
                ['department_code' => $entry['department']['department_code']],
                array_merge($entry['department'], [
                    'final_year_rule_type' => $entry['department']['final_year_rule_type'] ?? 'PROGRAMME_DEFINED',
                    'supports_project' => $entry['department']['supports_project'] ?? true,
                    'supports_research' => $entry['department']['supports_research'] ?? true,
                ])
            );

            foreach ($entry['programmes'] as $programmeData) {
                Program::query()->updateOrCreate(
                    ['programme_code' => $programmeData['programme_code']],
                    array_merge($programmeData, ['department_id' => $department->id])
                );
            }

            if (Schema::hasTable('department_workflow_rules')) {
                foreach ($entry['rules'] as $rule) {
                    DepartmentWorkflowRule::query()->updateOrCreate(
                        [
                            'department_id' => $department->id,
                            'academic_level' => $rule['academic_level'],
                        ],
                        [
                            'final_year' => $rule['final_year'],
                            'output_type' => $rule['output_type'],
                            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command?->info('AcademicStructureSeeder: departments, programmes, and workflow rules seeded.');
    }
}
