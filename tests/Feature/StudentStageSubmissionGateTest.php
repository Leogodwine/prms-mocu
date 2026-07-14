<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Models\Student;
use App\Models\User;
use App\Support\StudentStageProgress;
use Database\Seeders\ProjectStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentStageSubmissionGateTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProjectStageSeeder::class);
        $this->student = $this->createFinalYearStudent();
    }

    #[Test]
    public function research_chapter_is_blocked_until_complete_proposal_is_approved(): void
    {
        $researchStage = ProjectStage::query()->where('stage_name', 'Research Chapter 1')->firstOrFail();
        $latestByStage = collect();

        $reason = StudentStageProgress::canUploadStage(
            $researchStage->stage_name,
            $this->student,
            null,
            $latestByStage
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Complete Proposal Document', $reason);
    }

    #[Test]
    public function proposal_chapter_two_requires_chapter_one_approval(): void
    {
        $latestByStage = new Collection;

        $reason = StudentStageProgress::canUploadStage(
            'Proposal Chapter 2',
            $this->student,
            null,
            $latestByStage
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Chapter 1', $reason);
    }

    #[Test]
    public function proposal_chapter_one_is_allowed_without_prior_approval(): void
    {
        $reason = StudentStageProgress::canUploadStage(
            'Proposal Chapter 1',
            $this->student,
            null,
            collect()
        );

        $this->assertNull($reason);
    }

    #[Test]
    public function file_upload_to_research_chapter_is_rejected_without_proposal_completion(): void
    {
        Storage::fake('public');

        $stage = ProjectStage::query()->where('stage_name', 'Research Chapter 1')->firstOrFail();

        $this->actingAs($this->student)
            ->post(route('student.submissions.store'), [
                'stage_id' => $stage->id,
                'title' => 'Research draft',
                'document' => UploadedFile::fake()->create('chapter1.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('stage_id');
    }

    #[Test]
    public function blank_word_document_for_research_is_blocked_without_proposal_completion(): void
    {
        Storage::fake('public');
        $this->assertFileExists(resource_path('onlyoffice/blank.docx'));

        $stage = ProjectStage::query()->where('stage_name', 'Research Chapter 1')->firstOrFail();

        $this->actingAs($this->student)
            ->post(route('student.submissions.create-blank'), [
                'stage_id' => $stage->id,
                'title' => 'Untitled Document',
            ])
            ->assertSessionHasErrors('stage_id');

        $this->assertDatabaseCount('project_submissions', 0);
    }

    #[Test]
    public function research_chapter_one_is_allowed_after_complete_proposal_is_approved(): void
    {
        $this->approveStage('Proposal Chapter 1');
        $this->approveStage('Proposal Chapter 2');
        $this->approveStage('Proposal Chapter 3');
        $this->approveStage('Complete Proposal Document');

        $latestByStage = StudentStageProgress::latestSubmissionByStage($this->student, null);

        $reason = StudentStageProgress::canUploadStage(
            'Research Chapter 1',
            $this->student,
            null,
            $latestByStage
        );

        $this->assertNull($reason);
    }

    #[Test]
    public function research_chapter_two_requires_chapter_one_approval_even_after_proposal_is_complete(): void
    {
        $this->approveStage('Proposal Chapter 1');
        $this->approveStage('Proposal Chapter 2');
        $this->approveStage('Proposal Chapter 3');
        $this->approveStage('Complete Proposal Document');

        $latestByStage = StudentStageProgress::latestSubmissionByStage($this->student, null);

        $reason = StudentStageProgress::canUploadStage(
            'Research Chapter 2',
            $this->student,
            null,
            $latestByStage
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Chapter 1', $reason);
    }

    #[Test]
    public function progress_presentation_one_is_blocked_until_complete_system_is_approved(): void
    {
        $this->approveProposalTrack();

        $reason = StudentStageProgress::canUploadStage(
            'Progress Presentation 1',
            $this->student,
            null,
            StudentStageProgress::latestSubmissionByStage($this->student, null)
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Complete System', $reason);
    }

    #[Test]
    public function progress_presentation_one_is_allowed_after_complete_system_is_approved(): void
    {
        $this->approveProposalTrack();
        $this->approveStage(StudentStageProgress::completeSystemStageName());

        $reason = StudentStageProgress::canUploadStage(
            'Progress Presentation 1',
            $this->student,
            null,
            StudentStageProgress::latestSubmissionByStage($this->student, null)
        );

        $this->assertNull($reason);
    }

    #[Test]
    public function consent_letter_is_blocked_until_progress_presentation_three_is_approved(): void
    {
        $this->approveThroughProgressPresentations(2);

        $reason = StudentStageProgress::canUploadStage(
            \App\Support\RepositoryPublication::consentStageName(),
            $this->student,
            null,
            StudentStageProgress::latestSubmissionByStage($this->student, null)
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Presentation 3', $reason);
    }

    #[Test]
    public function final_presentation_is_blocked_until_consent_letter_is_approved(): void
    {
        $this->approveThroughProgressPresentations(3);

        $reason = StudentStageProgress::canUploadStage(
            'Final Presentation',
            $this->student,
            null,
            StudentStageProgress::latestSubmissionByStage($this->student, null)
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Consent Letter', $reason);
    }

    #[Test]
    public function complete_project_document_is_blocked_until_final_presentation_is_approved(): void
    {
        $this->approveThroughProgressPresentations(3);
        $this->approveStage(\App\Support\RepositoryPublication::consentStageName());

        $reason = StudentStageProgress::canUploadStage(
            'Complete Project Document',
            $this->student,
            null,
            StudentStageProgress::latestSubmissionByStage($this->student, null)
        );

        $this->assertNotNull($reason);
        $this->assertStringContainsString('Final Presentation', $reason);
    }

    #[Test]
    public function complete_project_document_is_allowed_after_final_presentation_is_approved(): void
    {
        $this->approveThroughProgressPresentations(3);
        $this->approveStage(\App\Support\RepositoryPublication::consentStageName());
        $this->approveStage('Final Presentation');

        $reason = StudentStageProgress::canUploadStage(
            'Complete Project Document',
            $this->student,
            null,
            StudentStageProgress::latestSubmissionByStage($this->student, null)
        );

        $this->assertNull($reason);
    }

    private function approveProposalTrack(): void
    {
        $this->approveStage('Proposal Chapter 1');
        $this->approveStage('Proposal Chapter 2');
        $this->approveStage('Proposal Chapter 3');
        $this->approveStage('Complete Proposal Document');
    }

    private function approveThroughProgressPresentations(int $count): void
    {
        $this->approveProposalTrack();
        $this->approveStage(StudentStageProgress::completeSystemStageName());

        foreach (range(1, $count) as $index) {
            $this->approveStage('Progress Presentation '.$index);
        }
    }

    private function createFinalYearStudent(): User
    {
        $department = Department::factory()->create([
            'department_code' => 'CICT',
            'department_name' => 'Computing and Information Technology',
        ]);

        $programme = Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BBICT',
            'duration_years' => 3,
            'final_year' => 3,
            'output_type' => 'both_allowed',
            'is_project_eligible' => true,
            'project_year' => 3,
        ]);

        $user = User::factory()->student()->create([
            'programme' => 'BBICT',
            'year_of_study' => 3,
            'enrollment_status' => 'active',
            'account_status' => 'active',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'programme_id' => $programme->id,
            'department_id' => $department->id,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);

        return $user->fresh();
    }

    private function approveStage(string $stageName): void
    {
        ProjectSubmission::query()->create([
            'student_id' => $this->student->id,
            'stage' => $stageName,
            'title' => $stageName,
            'version' => 1,
            'file_path' => 'submissions/test.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);
    }
}
