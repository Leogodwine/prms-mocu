<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\EvaluationRubric;
use App\Models\Program;
use App\Models\ProjectGroup;
use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Models\ProjectType;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StageDeadline;
use App\Models\Student;
use App\Models\SubmissionFeedback;
use App\Models\User;
use App\Support\PrmsAccountIdentifierFormat;
use App\Support\PublicPortalPublication;
use App\Support\StaffProfileProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Deterministic local/QA demo data for testing every major PRMS area.
 *
 * Prerequisites (usually already seeded):
 *   RoleSeeder, ProjectStageSeeder, AdminUserSeeder, AcademicStructureSeeder
 *
 * Run:
 *   php artisan db:seed --class=DevelopmentSeeder
 *   # or
 *   php artisan db:seed --class=DemoTestDataSeeder
 *
 * All demo accounts use password: password123
 *
 * Sign-in rules (same as production):
 * - Staff / admin: university email (e.g. coordinator.cict@mocu.ac.tz)
 * - Students: registration number only (e.g. MoCU/BBICT/501/20) — never email
 */
class DemoTestDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password123';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoTestDataSeeder must not run in production.');
        }

        $this->command?->warn('DemoTestDataSeeder: seeding deterministic QA data…');

        $this->ensureBootstrap();
        $demoFiles = $this->publishDemoFilesFromDocs();

        $cict = Department::query()->where('department_code', 'CICT')->first();
        $bbict = Program::query()->where('programme_code', 'BBICT')->first();

        if ($cict === null || $bbict === null) {
            throw new RuntimeException('CICT / BBICT missing. Run AcademicStructureSeeder first.');
        }

        $year = $this->seedAcademicCalendar();
        $this->seedProjectTypes();
        $this->seedRubrics();

        $hod = $this->seedStaffUser([
            'name' => 'CICT Head of Department',
            'email' => 'hod.cict@mocu.ac.tz',
            'login_id' => 'MoCU/CICT/101/20',
            'role' => 'hod',
            'department' => 'CICT',
            'gender' => 'male',
            'phone_number' => '+255738100101',
            'designation' => 'Head of Department',
        ]);

        $coordinator = $this->seedStaffUser([
            'name' => 'CICT Project Coordinator',
            'email' => 'coordinator.cict@mocu.ac.tz',
            'login_id' => 'MoCU/CICT/201/20',
            'role' => 'coordinator',
            'department' => 'CICT',
            'gender' => 'female',
            'phone_number' => '+255738100201',
            'designation' => 'Project Coordinator',
        ]);

        $supervisor1 = $this->seedStaffUser([
            'name' => 'Alice Supervisor',
            'email' => 'supervisor1.cict@mocu.ac.tz',
            'login_id' => 'MoCU/CICT/301/20',
            'role' => 'supervisor',
            'department' => 'CICT',
            'gender' => 'female',
            'phone_number' => '+255738100301',
            'designation' => 'Senior Lecturer',
        ]);

        $supervisor2 = $this->seedStaffUser([
            'name' => 'Bob Supervisor',
            'email' => 'supervisor2.cict@mocu.ac.tz',
            'login_id' => 'MoCU/CICT/302/20',
            'role' => 'supervisor',
            'department' => 'CICT',
            'gender' => 'male',
            'phone_number' => '+255738100302',
            'designation' => 'Lecturer',
        ]);

        // Extra ACC staff for multi-department HOD/coordinator smoke tests
        $this->seedStaffUser([
            'name' => 'ACC Head of Department',
            'email' => 'hod.acc@mocu.ac.tz',
            'login_id' => 'MoCU/ACC/101/20',
            'role' => 'hod',
            'department' => 'ACC',
            'gender' => 'female',
            'phone_number' => '+255738100111',
            'designation' => 'Head of Department',
        ]);

        $students = $this->seedStudents($bbict, $cict);

        $groupA = $this->seedGroup(
            'BBICT/2025/G01',
            $coordinator,
            [$students[0], $students[1]],
            $supervisor1,
            (int) explode('/', $year->year_name)[0]
        );

        $groupB = $this->seedGroup(
            'BBICT/2025/G02',
            $coordinator,
            [$students[2], $students[3]],
            $supervisor2,
            (int) explode('/', $year->year_name)[0]
        );

        // Solo / research-style group for one student
        $groupC = $this->seedGroup(
            'BBICT/2025/S01',
            $coordinator,
            [$students[4]],
            $supervisor1,
            (int) explode('/', $year->year_name)[0]
        );

        $this->seedDeadlines([$groupA, $groupB, $groupC], $year->year_name);
        $this->seedSubmissions($groupA, $students[0], $supervisor1, $demoFiles);
        $this->seedSubmissions($groupB, $students[2], $supervisor2, $demoFiles, lateStage: true);
        $this->seedSubmissions($groupC, $students[4], $supervisor1, $demoFiles, researchTrack: true);

        $publishedCount = 0;
        $publishedCount += $this->publishCompleteDocumentsToRepository($groupB, $students[2], [
            'Complete Proposal Document',
            'Complete Project Document',
        ]);
        $publishedCount += $this->publishCompleteDocumentsToRepository($groupC, $students[4], [
            'Complete Research Document',
        ]);

        $this->printCredentials($hod, $coordinator, $supervisor1, $supervisor2, $students, $publishedCount);
    }

    /**
     * Copy selected files from docs/ into storage/app/public/seed/demo/.
     *
     * @return array<string, array{path: string, original: string, mime: string, size: int}>
     */
    private function publishDemoFilesFromDocs(): array
    {
        $docsPath = base_path('docs');
        $disk = Storage::disk('public');
        $disk->makeDirectory('seed/demo');

        $map = [
            'proposal_ch1' => 'PROJECT PROPOSAL 01.docx',
            'proposal_ch2' => 'GROUP NO 14 PROJECT PROPOSAL CHAPTER 1 & 2.docx',
            'proposal_ch2_revision' => 'GROUP NO 14 PROJECT PROPOSAL CHAPTER 1 & 2 CORRECTIONS.docx',
            'proposal_ch3' => 'GROUP NO 14 PROJECT PROPOSAL CHAPTER 1, 2 & 3.docx',
            'complete_proposal' => 'GROUP NO 14 PROJECT PROPOSAL REVERSED CORRECT CHAPTER 1, 2 & 3.docx',
            'complete_system' => 'SMART LIBRARY NOISE MONITORING SYSTEM REPORT.docx',
            'complete_project' => 'SDLC.docx',
            'complete_research' => 'REQUIREMENT GATHERING AND ANALYSIS.docx',
            'progress_presentation' => 'GROUP 15.pdf',
            'research_ch1' => 'USMA_Proposal 1.docx',
            'research_ch2' => 'kawaya research.docx',
            'research_ch3' => 'Project_Proposal_with_Gantt.docx',
            'requirements' => 'REQUIREMENT GATHERING AND ANALYSIS.docx',
            'sdlc' => 'SDLC.docx',
            'showcase_home' => 'deepseek_mermaid_20260102_193528.png',
        ];

        $published = [];

        foreach ($map as $key => $sourceName) {
            $source = $docsPath.DIRECTORY_SEPARATOR.$sourceName;
            if (! is_file($source)) {
                $this->command?->warn("Demo file missing in docs/: {$sourceName}");

                continue;
            }

            $extension = strtolower(pathinfo($sourceName, PATHINFO_EXTENSION) ?: 'docx');
            $destRelative = 'seed/demo/'.$key.'.'.$extension;
            $destAbsolute = $disk->path($destRelative);

            File::ensureDirectoryExists(dirname($destAbsolute));
            File::copy($source, $destAbsolute);

            $published[$key] = [
                'path' => $destRelative,
                'original' => $sourceName,
                'mime' => $this->mimeForExtension($extension),
                'size' => (int) filesize($destAbsolute),
            ];
        }

        if ($published === []) {
            throw new RuntimeException('No demo files found under docs/. Cannot seed submission files.');
        }

        $this->command?->info('DemoTestDataSeeder: published '.count($published).' file(s) from docs/ to storage/app/public/seed/demo/.');

        return $published;
    }

    private function mimeForExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    private function ensureBootstrap(): void
    {
        if (Role::query()->count() === 0) {
            $this->call(RoleSeeder::class);
        }

        if (ProjectStage::query()->count() === 0) {
            $this->call(ProjectStageSeeder::class);
        }

        if (! User::query()->where('email', 'admin@mocu.ac.tz')->exists()) {
            $this->call(AdminUserSeeder::class);
        }

        if (Department::query()->count() === 0 || Program::query()->count() === 0) {
            $this->call(AcademicStructureSeeder::class);
        }
    }

    private function seedAcademicCalendar(): AcademicYear
    {
        $startYear = (int) date('Y');
        $yearName = $startYear.'/'.($startYear + 1);

        $year = AcademicYear::query()->updateOrCreate(
            ['year_name' => $yearName],
            [
                'start_date' => now()->year($startYear)->month(10)->day(1)->startOfDay(),
                'end_date' => now()->year($startYear + 1)->month(9)->day(30)->endOfDay(),
                'is_current' => true,
            ]
        );

        AcademicYear::query()
            ->where('id', '!=', $year->id)
            ->update(['is_current' => false]);

        if (Schema::hasTable('semesters')) {
            Semester::query()->updateOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'semester_number' => 1,
                ],
                [
                    'semester_name' => 'Semester I',
                    'start_date' => $year->start_date,
                    'end_date' => now()->year($startYear + 1)->month(2)->day(28)->endOfDay(),
                    'is_current' => true,
                ]
            );

            Semester::query()->updateOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'semester_number' => 2,
                ],
                [
                    'semester_name' => 'Semester II',
                    'start_date' => now()->year($startYear + 1)->month(3)->day(1)->startOfDay(),
                    'end_date' => $year->end_date,
                    'is_current' => false,
                ]
            );

            Semester::query()
                ->where('academic_year_id', $year->id)
                ->where('semester_number', '!=', 1)
                ->update(['is_current' => false]);
        }

        return $year;
    }

    private function seedProjectTypes(): void
    {
        if (! Schema::hasTable('project_types')) {
            return;
        }

        $types = [
            [
                'type_name' => 'Computer-based project',
                'description' => 'Group software / ICT system project for final-year students.',
                'min_students' => 2,
                'max_students' => 4,
                'is_group_based' => true,
            ],
            [
                'type_name' => 'Undergraduate dissertation',
                'description' => 'Individual research dissertation.',
                'min_students' => 1,
                'max_students' => 1,
                'is_group_based' => false,
            ],
            [
                'type_name' => 'Research thesis',
                'description' => 'Postgraduate research thesis.',
                'min_students' => 1,
                'max_students' => 1,
                'is_group_based' => false,
            ],
        ];

        foreach ($types as $type) {
            ProjectType::query()->updateOrCreate(
                ['type_name' => $type['type_name']],
                $type
            );
        }
    }

    private function seedRubrics(): void
    {
        if (! Schema::hasTable('evaluation_rubrics')) {
            return;
        }

        $rubric = EvaluationRubric::query()->updateOrCreate(
            ['name' => 'University standard presentation scheme'],
            [
                'description' => 'Default MoCU presentation grading scheme for supervisors.',
                'criteria' => [
                    ['name' => 'Content quality', 'weight' => 40, 'description' => 'Depth and correctness of content'],
                    ['name' => 'Methodology', 'weight' => 25, 'description' => 'Sound approach and justification'],
                    ['name' => 'Presentation', 'weight' => 20, 'description' => 'Clarity and delivery'],
                    ['name' => 'Q&A response', 'weight' => 15, 'description' => 'Ability to defend the work'],
                ],
                'total_marks' => 100,
                'is_active' => true,
                'is_system_default' => false,
            ]
        );

        EvaluationRubric::setSystemDefault($rubric);

        EvaluationRubric::query()->updateOrCreate(
            ['name' => 'Legacy CICT scheme'],
            [
                'description' => 'Inactive legacy scheme kept for reference.',
                'criteria' => [
                    ['name' => 'Quality', 'weight' => 50, 'description' => 'Overall quality'],
                    ['name' => 'Completeness', 'weight' => 50, 'description' => 'Coverage of requirements'],
                ],
                'total_marks' => 100,
                'is_active' => false,
                'is_system_default' => false,
            ]
        );
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     login_id: string,
     *     role: string,
     *     department: string,
     *     gender: string,
     *     phone_number: string,
     *     designation?: string
     * }  $data
     */
    private function seedStaffUser(array $data): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'login_id' => $data['login_id'],
                'staff_id' => $data['login_id'],
                'registration_number' => null,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role' => $data['role'],
                'account_status' => 'active',
                'enrollment_status' => 'active',
                'department' => $data['department'],
                'programme' => null,
                'year_of_study' => null,
                'gender' => $data['gender'],
                'phone_number' => $data['phone_number'],
                'must_change_password' => false,
                'email_verified_at' => now(),
                'notify_email_new_submission' => true,
                'notify_email_submission_reviewed' => true,
                'notify_email_workflow' => true,
                'notify_sms_workflow' => true,
            ]
        );

        $this->attachRole($user, $data['role']);
        StaffProfileProvisioner::syncFromUser($user, $data['gender']);

        if (Schema::hasTable('staff') && filled($data['designation'] ?? null)) {
            $profile = $user->staffProfile;
            if ($profile !== null) {
                $profile->update(['designation' => $data['designation']]);
            }
        }

        return $user->fresh();
    }

    /**
     * @return list<User>
     */
    private function seedStudents(Program $programme, Department $department): array
    {
        $defs = [
            ['name' => 'Jane Demo Student', 'email' => 'student001@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/501/20', 'gender' => 'female', 'phone' => '+255738200501', 'year' => 3, 'track' => 'project'],
            ['name' => 'John Demo Student', 'email' => 'student002@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/502/20', 'gender' => 'male', 'phone' => '+255738200502', 'year' => 3, 'track' => 'project'],
            ['name' => 'Asha Demo Student', 'email' => 'student003@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/503/20', 'gender' => 'female', 'phone' => '+255738200503', 'year' => 3, 'track' => 'project'],
            ['name' => 'Juma Demo Student', 'email' => 'student004@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/504/20', 'gender' => 'male', 'phone' => '+255738200504', 'year' => 3, 'track' => 'project'],
            ['name' => 'Neema Research Student', 'email' => 'student005@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/505/20', 'gender' => 'female', 'phone' => '+255738200505', 'year' => 3, 'track' => 'research'],
            ['name' => 'Peter Ungrouped Student', 'email' => 'student006@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/506/20', 'gender' => 'male', 'phone' => '+255738200506', 'year' => 3, 'track' => 'project'],
            ['name' => 'Fatuma Year2 Student', 'email' => 'student007@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/507/21', 'gender' => 'female', 'phone' => '+255738200507', 'year' => 2, 'track' => null],
            ['name' => 'Pending Reset Student', 'email' => 'student008@student.mocu.ac.tz', 'reg' => 'MoCU/BBICT/508/20', 'gender' => 'male', 'phone' => '+255738200508', 'year' => 3, 'track' => 'project', 'must_change_password' => true],
        ];

        $users = [];

        foreach ($defs as $def) {
            if (! PrmsAccountIdentifierFormat::isValidRegistrationNumber($def['reg'])) {
                throw new RuntimeException(
                    "Invalid demo student registration number [{$def['reg']}]. ".PrmsAccountIdentifierFormat::STUDENT_HELP
                );
            }

            if (! PrmsAccountIdentifierFormat::registrationMatchesProgramme($def['reg'], $programme->programme_code)) {
                throw new RuntimeException(
                    "Demo reg [{$def['reg']}] does not match programme [{$programme->programme_code}]."
                );
            }

            $user = User::query()->updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'login_id' => $def['reg'],
                    'registration_number' => $def['reg'],
                    'staff_id' => null,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'role' => 'student',
                    'account_status' => 'active',
                    'enrollment_status' => 'active',
                    'department' => $department->department_code,
                    'programme' => $programme->programme_code,
                    'year_of_study' => $def['year'],
                    'gender' => $def['gender'],
                    'phone_number' => $def['phone'],
                    'must_change_password' => (bool) ($def['must_change_password'] ?? false),
                    'email_verified_at' => now(),
                    'notify_email_new_submission' => true,
                    'notify_email_submission_reviewed' => true,
                    'notify_email_workflow' => true,
                    'notify_sms_workflow' => true,
                ]
            );

            $this->attachRole($user, 'student');

            $studentAttrs = [
                'registration_number' => $def['reg'],
                'full_name' => $def['name'],
                'gender' => $def['gender'],
                'programme_id' => $programme->id,
                'year_of_study' => $def['year'],
                'enrollment_status' => 'active',
                'university_email' => $def['email'],
                'phone_number' => $def['phone'],
                'admission_date' => now()->subYears(max(1, $def['year']))->toDateString(),
                'expected_graduation' => now()->addYears(max(1, 4 - $def['year']))->toDateString(),
            ];

            if (Schema::hasColumn('students', 'department_id')) {
                $studentAttrs['department_id'] = $department->id;
            }
            if (Schema::hasColumn('students', 'academic_level')) {
                $studentAttrs['academic_level'] = 'bachelor';
            }
            if (Schema::hasColumn('students', 'workflow_role')) {
                $studentAttrs['workflow_role'] = $def['year'] >= 3 ? 'ACTIVE_CANDIDATE' : 'VIEWER_ONLY';
            }
            if (Schema::hasColumn('students', 'output_track')) {
                $studentAttrs['output_track'] = $def['track'];
            }

            Student::query()->updateOrCreate(
                ['user_id' => $user->id],
                $studentAttrs
            );

            $users[] = $user->fresh();
        }

        return $users;
    }

    /**
     * @param  list<User>  $members
     */
    private function seedGroup(
        string $name,
        User $coordinator,
        array $members,
        User $supervisor,
        int $academicYear,
    ): ProjectGroup {
        $group = ProjectGroup::query()->updateOrCreate(
            ['name' => $name],
            [
                'coordinator_id' => $coordinator->id,
                'academic_year' => $academicYear,
            ]
        );

        $memberIds = collect($members)->pluck('id')->all();
        $group->members()->sync($memberIds);

        $leadStudentId = $members[0]->id;

        \App\Models\SupervisorAssignment::query()->updateOrCreate(
            ['project_group_id' => $group->id],
            [
                'supervisor_id' => $supervisor->id,
                'student_id' => $leadStudentId,
            ]
        );

        return $group->fresh(['members', 'supervisorAssignment']);
    }

    /**
     * @param  list<ProjectGroup>  $groups
     */
    private function seedDeadlines(array $groups, string $yearName): void
    {
        if (! Schema::hasTable('stage_deadlines')) {
            return;
        }

        $stages = [
            'Proposal Chapter 1',
            'Complete Proposal Document',
            'Complete System',
            'Progress Presentation 1',
        ];

        foreach ($groups as $index => $group) {
            foreach ($stages as $offset => $stageName) {
                StageDeadline::query()->updateOrCreate(
                    [
                        'project_group_id' => $group->id,
                        'stage_name' => $stageName,
                        'academic_year' => $yearName,
                    ],
                    [
                        'start_time' => now()->subDays(7 + $offset)->startOfDay(),
                        'end_time' => now()->addDays(14 + ($offset * 7) + $index)->endOfDay(),
                    ]
                );
            }
        }
    }

    /**
     * @param  array<string, array{path: string, original: string, mime: string, size: int}>  $demoFiles
     */
    private function seedSubmissions(
        ProjectGroup $group,
        User $student,
        User $supervisor,
        array $demoFiles,
        bool $lateStage = false,
        bool $researchTrack = false,
    ): void {
        if (! Schema::hasTable('project_submissions')) {
            return;
        }

        $rows = $researchTrack
            ? [
                ['stage' => 'Proposal Chapter 1', 'title' => 'Research proposal intro', 'status' => 'approved', 'version' => 2, 'file' => 'research_ch1'],
                ['stage' => 'Proposal Chapter 2', 'title' => 'Literature review draft', 'status' => 'needs_revision', 'version' => 1, 'file' => 'research_ch2'],
                ['stage' => 'Proposal Chapter 3', 'title' => 'Methodology outline', 'status' => 'pending', 'version' => 1, 'file' => 'research_ch3'],
                ['stage' => 'Complete Research Document', 'title' => 'Complete research dissertation', 'status' => 'approved', 'version' => 1, 'file' => 'complete_research', 'description' => 'Final research document published to the public repository.'],
            ]
            : (
                $lateStage
                    ? [
                        ['stage' => 'Proposal Chapter 1', 'title' => 'System proposal ch.1', 'status' => 'approved', 'version' => 1, 'file' => 'proposal_ch1'],
                        ['stage' => 'Complete Proposal Document', 'title' => 'Full proposal pack', 'status' => 'approved', 'version' => 1, 'file' => 'complete_proposal', 'description' => 'Complete proposal package for the MoCU inventory project.'],
                        ['stage' => 'Complete System', 'title' => 'Smart library noise monitoring system', 'status' => 'approved', 'version' => 1, 'file' => 'complete_system', 'description' => 'A demo system for monitoring noise levels in MoCU libraries.', 'screenshot' => 'showcase_home'],
                        ['stage' => 'Progress Presentation 1', 'title' => 'Progress presentation 1 slides', 'status' => 'pending', 'version' => 1, 'file' => 'progress_presentation'],
                        ['stage' => 'Final Presentation Consent Letter', 'title' => 'Final presentation consent', 'status' => 'approved', 'version' => 1, 'file' => 'complete_proposal', 'consent' => true],
                        ['stage' => 'Complete Project Document', 'title' => 'Complete project document', 'status' => 'approved', 'version' => 1, 'file' => 'complete_project', 'description' => 'Final project documentation published to the public repository.'],
                    ]
                    : [
                        ['stage' => 'Proposal Chapter 1', 'title' => 'Introduction chapter', 'status' => 'approved', 'version' => 2, 'file' => 'proposal_ch1'],
                        ['stage' => 'Proposal Chapter 2', 'title' => 'Related work chapter', 'status' => 'submitted', 'version' => 1, 'file' => 'proposal_ch2'],
                        ['stage' => 'Proposal Chapter 3', 'title' => 'Methodology chapter', 'status' => 'pending', 'version' => 1, 'file' => 'proposal_ch3'],
                    ]
            );

        foreach ($rows as $row) {
            $fileKey = $row['file'];
            $file = $demoFiles[$fileKey] ?? $demoFiles['proposal_ch1'] ?? null;
            if ($file === null) {
                $this->command?->warn("Skipping submission without demo file: {$row['stage']}");

                continue;
            }

            $payload = [
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'status' => $row['status'],
                'file_path' => $file['path'],
                'original_filename' => $file['original'],
                'mime_type' => $file['mime'],
                'file_size' => $file['size'],
                'submitted_at' => now()->subDays(3),
                'submitted_to_coordinator' => in_array($row['status'], ['approved', 'needs_revision'], true),
                'coordinator_approved_at' => $row['status'] === 'approved' ? now()->subDay() : null,
                'demo_url' => ($row['stage'] === 'Complete System') ? 'https://example.com/demo/library-noise' : null,
            ];

            if (! empty($row['consent']) && $row['status'] === 'approved') {
                $payload['supervisor_consent_signed_at'] = now()->subDays(2);
                $payload['supervisor_consent_signed_by'] = $supervisor->id;
                $payload['submitted_to_coordinator'] = true;
                $payload['coordinator_approved_at'] = now()->subDay();
            }

            if (! empty($row['screenshot']) && isset($demoFiles[$row['screenshot']])) {
                $shot = $demoFiles[$row['screenshot']];
                $payload['screenshot_path'] = $shot['path'];
                $payload['screenshot_original_filename'] = $shot['original'];
                $payload['screenshot_mime_type'] = $shot['mime'];
            }

            $submission = ProjectSubmission::query()->updateOrCreate(
                [
                    'project_group_id' => $group->id,
                    'student_id' => $student->id,
                    'stage' => $row['stage'],
                    'version' => $row['version'],
                ],
                $payload
            );

            if ($row['status'] === 'needs_revision' && Schema::hasTable('submission_feedback')) {
                SubmissionFeedback::query()->updateOrCreate(
                    [
                        'project_submission_id' => $submission->id,
                        'supervisor_id' => $supervisor->id,
                    ],
                    [
                        'comments' => 'Please expand the literature review with at least five recent peer-reviewed sources.',
                        'decision' => 'needs_revision',
                    ]
                );
            }

            if ($row['status'] === 'approved' && Schema::hasTable('submission_feedback')) {
                SubmissionFeedback::query()->updateOrCreate(
                    [
                        'project_submission_id' => $submission->id,
                        'supervisor_id' => $supervisor->id,
                    ],
                    [
                        'comments' => 'Approved. Proceed to the next stage.',
                        'decision' => 'approved',
                    ]
                );
            }
        }
    }

    /**
     * Mark complete-document submissions as repository-published and mirror them
     * into research_projects / documents for /research.
     *
     * @param  list<string>  $stages
     */
    private function publishCompleteDocumentsToRepository(ProjectGroup $group, User $student, array $stages): int
    {
        if (! Schema::hasTable('project_submissions')
            || ! Schema::hasTable('research_projects')
            || ! Schema::hasTable('documents')) {
            return 0;
        }

        $published = 0;

        foreach ($stages as $stage) {
            $submission = ProjectSubmission::query()
                ->where('project_group_id', $group->id)
                ->where('student_id', $student->id)
                ->where('stage', $stage)
                ->orderByDesc('version')
                ->orderByDesc('id')
                ->first();

            if ($submission === null || ! filled($submission->file_path)) {
                $this->command?->warn("Cannot publish missing complete document: {$stage}");

                continue;
            }

            $submission->forceFill([
                'status' => 'approved',
                'submitted_to_coordinator' => true,
                'coordinator_approved_at' => $submission->coordinator_approved_at ?? now()->subDay(),
                'repository_published_at' => $submission->repository_published_at ?? now()->subDays(2),
            ])->save();

            $project = PublicPortalPublication::syncSubmission($submission->fresh([
                'student.studentProfile.programme',
                'projectGroup.supervisorAssignment',
            ]));

            if ($project !== null) {
                $published++;
                $this->command?->info("Public repository: published [{$stage}] as \"{$project->title}\".");
            }
        }

        return $published;
    }

    private function attachRole(User $user, string $roleName): void
    {
        $role = Role::query()->where('role_name', $roleName)->first();
        if ($role === null) {
            return;
        }

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_at' => now(),
                'is_active' => true,
            ],
        ]);
    }

    /**
     * @param  list<User>  $students
     */
    private function printCredentials(
        User $hod,
        User $coordinator,
        User $supervisor1,
        User $supervisor2,
        array $students,
        int $publishedCount = 0,
    ): void {
        $this->command?->newLine();
        $this->command?->info('Demo accounts ready (password for all: '.self::DEMO_PASSWORD.')');
        $this->command?->warn('Students must sign in with registration number (MoCU/PROGRAMME/NUMBER/YY), not email.');
        $this->command?->warn('Staff / admin must sign in with university email.');
        $this->command?->table(
            ['Role', 'Sign-in username', 'Notes'],
            [
                ['admin', 'admin@mocu.ac.tz', 'staff email'],
                ['hod', $hod->email, 'staff email'],
                ['coordinator', $coordinator->email, 'staff email'],
                ['supervisor', $supervisor1->email, 'staff email'],
                ['supervisor', $supervisor2->email, 'staff email'],
                ['student (grouped)', $students[0]->login_id, 'reg. no. — has seeded submissions'],
                ['student (ungrouped)', $students[5]->login_id, 'reg. no.'],
                ['student (reset pending)', $students[7]->login_id, 'reg. no. — must change password'],
            ]
        );
        $this->command?->line('Example student sign-in: '.$students[0]->login_id.' / '.self::DEMO_PASSWORD);
        $this->command?->line('Groups: BBICT/2025/G01, BBICT/2025/G02, BBICT/2025/S01');
        $this->command?->line("Public repository: {$publishedCount} published item(s) — browse /research");
        $this->command?->line('Also seeded: academic year/semesters, project types, rubrics, deadlines, submissions + feedback.');
    }
}
