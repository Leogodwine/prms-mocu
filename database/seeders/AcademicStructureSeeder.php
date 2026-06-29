<?php

namespace Database\Seeders;

use App\Models\AcademicLevelSetting;
use App\Models\Department;
use App\Models\DepartmentWorkflowRule;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * MoCU academic departments, programmes, and final-year workflow rules.
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
            $this->department(
                'CICT',
                'Computing and Information Technology',
                'cict@mocu.ac.tz',
                [
                    $this->bachelor('BBICT', 'Bachelor of Business Information and Communication Technology', 'BOTH_ALLOWED', true),
                    $this->bachelor('BSDS', 'Bachelor of Science in Data Science'),
                    $this->diploma('DBICT', 'Diploma in Business Information and Communication Technology', 'PROJECT_ONLY', true),
                    $this->certificate('CIT', 'Certificate in Information Technology'),
                ],
                [
                    $this->rule('bachelor', 3, 'BOTH_ALLOWED'),
                    $this->rule('diploma', 2, 'NONE'),
                ],
            ),
            $this->department(
                'ACC',
                'Accounting and Finance',
                'accounting@mocu.ac.tz',
                [
                    $this->bachelor('BAF', 'Bachelor of Accounting and Finance'),
                    $this->bachelor('BAT', 'Bachelor of Accounting and Taxation'),
                    $this->certificate('CAF', 'Certificate in Accounting and Finance'),
                    $this->postgradDiploma('PGD-AF', 'Postgraduate Diploma in Accounting and Finance', 1),
                ],
            ),
            $this->department(
                'COOP',
                'Co-operative Management and Accounting',
                'cooperative@mocu.ac.tz',
                [
                    $this->bachelor('BCMA', 'Bachelor of Co-operative Management and Accounting'),
                    $this->diploma('DCMA', 'Diploma in Co-operative Management and Accounting'),
                    $this->certificate('CMA', 'Certificate in Co-operative Management and Accounting'),
                    $this->postgradDiploma('PGD-CBM', 'Postgraduate Diploma in Co-operative Business Management', 2),
                    $this->postgradDiploma('PGD-SCM', 'Postgraduate Diploma in Savings and Credit Management', 1),
                ],
            ),
            $this->department(
                'BANK',
                'Banking and Microfinance',
                'banking@mocu.ac.tz',
                [
                    $this->bachelor('BBMF', 'Bachelor of Banking and Microfinance'),
                    $this->diploma('DMFM', 'Diploma in Microfinance Management'),
                    $this->certificate('CMF', 'Certificate in Microfinance Management'),
                ],
            ),
            $this->department(
                'HRM',
                'Human Resource Management',
                'hrm@mocu.ac.tz',
                [
                    $this->bachelor('BHRM', 'Bachelor of Human Resource Management'),
                    $this->diploma('DHRM', 'Diploma in Human Resource Management'),
                    $this->certificate('CHRM', 'Certificate in Human Resource Management'),
                    $this->masters('MHRM-HD', 'Master of Human Resource Management'),
                ],
            ),
            $this->department(
                'BUS',
                'Business, Marketing and Enterprise Management',
                'business@mocu.ac.tz',
                [
                    $this->bachelor('BBM', 'Bachelor of Marketing Management'),
                    $this->bachelor('BPSCM', 'Bachelor of Procurement and Supply Chain Management'),
                    $this->bachelor('BEC', 'Bachelor of Economics'),
                    $this->diploma('DBEM', 'Diploma in Business Enterprise Management'),
                    $this->masters('MBM-HD', 'Master of Business Management'),
                    $this->masters('MA-PSM-HD', 'Master of Arts in Procurement and Supply Management'),
                ],
            ),
            $this->department(
                'LAW',
                'Law',
                'law@mocu.ac.tz',
                [
                    $this->bachelor('LLB', 'Bachelor of Laws'),
                    $this->certificate('CL', 'Certificate in Law'),
                ],
            ),
            $this->department(
                'LIS',
                'Library and Information Science',
                'library@mocu.ac.tz',
                [
                    $this->diploma('DLIS', 'Diploma in Library and Information Science'),
                    $this->certificate('CLIS', 'Certificate in Library and Information Science'),
                ],
            ),
            $this->department(
                'CED',
                'Community and Economic Development',
                'ced@mocu.ac.tz',
                [
                    $this->bachelor('BCED', 'Bachelor of Community Economic Development'),
                    $this->masters('MA-CCD', 'Master of Arts in Co-operative and Community Development'),
                    $this->masters('MA-CD', 'Master of Community Development'),
                    $this->masters('MDP', 'Master in Development Planning'),
                    $this->postgradDiploma('PGD-CD', 'Postgraduate Diploma in Community Development', 2),
                ],
            ),
            $this->department(
                'PPM',
                'Project Planning and Management',
                'ppm@mocu.ac.tz',
                [
                    $this->masters('MPPM', 'Master of Project Planning and Management'),
                ],
            ),
            $this->department(
                'EDU',
                'Education',
                'education@mocu.ac.tz',
                [
                    $this->bachelor('BED', 'Bachelor of Education'),
                ],
                [
                    $this->rule('bachelor', 3, 'RESEARCH_ONLY'),
                ],
            ),
            $this->department(
                'AGR',
                'Agriculture and Trade',
                'agriculture@mocu.ac.tz',
                [
                    $this->certificate('CQT', 'Certificate in Coffee Quality and Trade'),
                ],
            ),
            $this->department(
                'GRAD',
                'Graduate Studies and Research',
                'graduate@mocu.ac.tz',
                [
                    $this->phd('PhD', 'Doctor of Philosophy'),
                ],
                [
                    $this->rule('phd', 3, 'RESEARCH_ONLY'),
                ],
            ),
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

        $this->syncLevelSettings();

        $programmeCount = Program::query()->count();
        $departmentCount = Department::query()->count();
        $this->command?->info("AcademicStructureSeeder: {$departmentCount} department(s) and {$programmeCount} programme(s) seeded.");
    }

    /**
     * @param  list<array<string, mixed>>  $programmes
     * @param  list<array<string, mixed>>  $rules
     * @return array{department: array<string, mixed>, programmes: list<array<string, mixed>>, rules: list<array<string, mixed>>}
     */
    private function department(string $code, string $name, string $email, array $programmes, array $rules = []): array
    {
        if ($rules === []) {
            $rules = [
                $this->rule('bachelor', 3, 'RESEARCH_ONLY'),
                $this->rule('diploma', 2, 'NONE'),
                $this->rule('masters', 2, 'RESEARCH_ONLY'),
            ];
        }

        return [
            'department' => [
                'department_code' => $code,
                'department_name' => $name,
                'head_of_department' => null,
                'contact_email' => $email,
            ],
            'programmes' => $programmes,
            'rules' => $rules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rule(string $academicLevel, int $finalYear, string $outputType): array
    {
        return [
            'academic_level' => $academicLevel,
            'final_year' => $finalYear,
            'output_type' => $outputType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bachelor(
        string $code,
        string $name,
        string $outputType = 'RESEARCH_ONLY',
        bool $projectEligible = false,
    ): array {
        return [
            'programme_code' => $code,
            'programme_name' => $name,
            'duration_years' => 3,
            'academic_level' => 'bachelor',
            'final_year' => 3,
            'output_type' => $outputType,
            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
            'is_project_eligible' => $projectEligible,
            'project_year' => 3,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function diploma(
        string $code,
        string $name,
        string $outputType = 'NONE',
        bool $projectEligible = false,
    ): array {
        return [
            'programme_code' => $code,
            'programme_name' => $name,
            'duration_years' => 2,
            'academic_level' => 'diploma',
            'final_year' => 2,
            'output_type' => $outputType,
            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
            'is_project_eligible' => $projectEligible,
            'project_year' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function certificate(string $code, string $name): array
    {
        return [
            'programme_code' => $code,
            'programme_name' => $name,
            'duration_years' => 1,
            'academic_level' => 'certificate',
            'final_year' => 1,
            'output_type' => 'NONE',
            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
            'is_project_eligible' => false,
            'project_year' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function masters(string $code, string $name): array
    {
        return [
            'programme_code' => $code,
            'programme_name' => $name,
            'duration_years' => 2,
            'academic_level' => 'masters',
            'final_year' => 2,
            'output_type' => 'RESEARCH_ONLY',
            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
            'is_project_eligible' => false,
            'project_year' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function postgradDiploma(string $code, string $name, int $durationYears): array
    {
        return [
            'programme_code' => $code,
            'programme_name' => $name,
            'duration_years' => $durationYears,
            'academic_level' => 'masters',
            'final_year' => $durationYears,
            'output_type' => 'RESEARCH_ONLY',
            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
            'is_project_eligible' => false,
            'project_year' => $durationYears,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function phd(string $code, string $name): array
    {
        return [
            'programme_code' => $code,
            'programme_name' => $name,
            'duration_years' => 3,
            'academic_level' => 'phd',
            'final_year' => 3,
            'output_type' => 'RESEARCH_ONLY',
            'workflow_type' => 'STANDARD_RESEARCH_WORKFLOW',
            'is_project_eligible' => false,
            'project_year' => 3,
        ];
    }

    private function syncLevelSettings(): void
    {
        if (! Schema::hasTable('academic_level_settings')) {
            return;
        }

        AcademicLevelSetting::query()->updateOrCreate(
            ['academic_level' => 'diploma'],
            [
                'final_year_default' => 2,
                'final_stage_definition' => 'Final semester / year of diploma programme',
                'workflow_complexity' => 'simplified',
                'output_rules' => [
                    'default_output_type' => 'NONE',
                    'supports_project' => false,
                    'supports_research' => false,
                ],
            ]
        );

        AcademicLevelSetting::query()->updateOrCreate(
            ['academic_level' => 'certificate'],
            [
                'final_year_default' => 1,
                'final_stage_definition' => 'Certificate programme (no PRMS research or project)',
                'workflow_complexity' => 'simplified',
                'output_rules' => [
                    'default_output_type' => 'NONE',
                    'supports_project' => false,
                    'supports_research' => false,
                ],
            ]
        );
    }
}
