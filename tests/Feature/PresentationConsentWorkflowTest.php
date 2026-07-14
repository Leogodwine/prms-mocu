<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\ProjectGroup;
use App\Models\ProjectStage;
use App\Models\ProjectSubmission;
use App\Models\Student;
use App\Models\SubmissionFeedback;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\RepositoryPublication;
use App\Support\StudentStageProgress;
use Database\Seeders\ProjectStageSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PresentationConsentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $supervisor;

    private User $coordinator;

    private ProjectGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(ProjectStageSeeder::class);
        Storage::fake('public');
        Notification::fake();

        [$this->student, $this->supervisor, $this->coordinator, $this->group] = $this->createConsentWorkspace();
        $this->approveThroughProgressPresentations(3);
    }

    #[Test]
    public function student_can_submit_consent_request_with_presentation_date_without_file(): void
    {
        $stage = $this->consentStage();
        $presentationDate = now()->addWeek()->toDateString();

        $this->actingAs($this->student)
            ->post(route('student.submissions.store'), [
                'stage_id' => $stage->id,
                'presentation_date' => $presentationDate,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $submission = ProjectSubmission::query()->where('stage', $stage->stage_name)->first();

        $this->assertNotNull($submission);
        $this->assertSame('pending', $submission->status);
        $this->assertNull($submission->file_path);
        $this->assertSame($presentationDate, $submission->presentation_date?->toDateString());
        $this->assertSame($this->group->id, $submission->project_group_id);
    }

    #[Test]
    public function supervisor_reject_requires_reason_for_consent(): void
    {
        $submission = $this->createPendingConsentSubmission();

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.review', $submission), [
                'decision' => 'needs_revision',
                'comments' => '',
            ])
            ->assertSessionHasErrors('comments');

        $submission->refresh();
        $this->assertSame('pending', $submission->status);
    }

    #[Test]
    public function supervisor_can_return_consent_to_student_with_reason(): void
    {
        $submission = $this->createPendingConsentSubmission();

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.review', $submission), [
                'decision' => 'needs_revision',
                'comments' => 'Please choose a weekday presentation date.',
            ])
            ->assertRedirect(route('supervisor.index'));

        $submission->refresh();

        $this->assertSame('needs_revision', $submission->status);
        $this->assertDatabaseHas('submission_feedback', [
            'project_submission_id' => $submission->id,
            'supervisor_id' => $this->supervisor->id,
            'decision' => 'needs_revision',
        ]);

        $feedback = SubmissionFeedback::query()->where('project_submission_id', $submission->id)->first();
        $this->assertStringContainsString('weekday', (string) $feedback?->comments);
    }

    #[Test]
    public function supervisor_can_sign_and_forward_consent_to_coordinator(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for consent PDF generation.');
        }

        $submission = $this->createPendingConsentSubmission();
        $presentationDate = now()->addWeeks(2)->toDateString();

        $this->actingAs($this->supervisor)
            ->post(route('supervisor.presentation-consent.sign.store', $submission), [
                'consent_agreed' => '1',
                'signature' => $this->fakeSignatureDataUri(),
                'presentation_date' => $presentationDate,
                'consent_project_title' => 'Smart Campus Navigation System',
                'consent_group_number' => $this->group->name,
                'comments' => 'Ready for final presentation.',
            ])
            ->assertRedirect(route('supervisor.index'))
            ->assertSessionHas('status');

        $submission->refresh();

        $this->assertSame('approved', $submission->status);
        $this->assertTrue($submission->submitted_to_coordinator);
        $this->assertNotNull($submission->supervisor_consent_signed_at);
        $this->assertNotNull($submission->supervisor_signature_path);
        $this->assertNotNull($submission->supervisor_consent_pdf_path);
        $this->assertSame($presentationDate, $submission->presentation_date?->toDateString());
        $this->assertSame('Smart Campus Navigation System', $submission->consent_project_title);
        $this->assertSame($this->group->name, $submission->consent_group_number);
        Storage::disk('public')->assertExists((string) $submission->supervisor_consent_pdf_path);
    }

    #[Test]
    public function student_can_view_supervisor_signed_consent_pdf(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for consent PDF generation.');
        }

        $submission = $this->supervisorSignConsent($this->createPendingConsentSubmission());

        $this->actingAs($this->student)
            ->get(route('student.presentation-consent.pdf', $submission))
            ->assertOk();
    }

    #[Test]
    public function coordinator_can_finalize_consent_after_supervisor_signs(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for consent PDF generation.');
        }

        $submission = $this->supervisorSignConsent($this->createPendingConsentSubmission());

        $this->actingAs($this->coordinator)
            ->post(route('coordinator.submissions.consent.sign.store', $submission), [
                'consent_reviewed' => '1',
                'signature' => $this->fakeSignatureDataUri(),
            ])
            ->assertRedirect(route('coordinator.submissions'))
            ->assertSessionHas('status');

        $submission->refresh();

        $this->assertNotNull($submission->coordinator_approved_at);
        $this->assertSame($this->coordinator->id, $submission->coordinator_approved_by);
        $this->assertNotNull($submission->coordinator_signature_path);
        $this->assertNotNull($submission->coordinator_consent_pdf_path);
        Storage::disk('public')->assertExists((string) $submission->coordinator_consent_pdf_path);
    }

    #[Test]
    public function full_consent_workflow_from_student_to_coordinator_finalization(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for consent PDF generation.');
        }

        $stage = $this->consentStage();
        $presentationDate = now()->addDays(10)->toDateString();

        $this->actingAs($this->student)
            ->post(route('student.submissions.store'), [
                'stage_id' => $stage->id,
                'presentation_date' => $presentationDate,
            ])
            ->assertRedirect();

        $submission = ProjectSubmission::query()->where('stage', $stage->stage_name)->firstOrFail();
        $this->assertSame('pending', $submission->status);

        $this->supervisorSignConsent($submission);

        $submission->refresh();
        $this->assertTrue($submission->submitted_to_coordinator);

        $this->actingAs($this->coordinator)
            ->post(route('coordinator.submissions.consent.sign.store', $submission), [
                'consent_reviewed' => '1',
                'signature' => $this->fakeSignatureDataUri(),
            ])
            ->assertRedirect(route('coordinator.submissions'));

        $submission->refresh();

        $this->assertSame('approved', $submission->status);
        $this->assertNotNull($submission->coordinator_approved_at);
        $this->assertNotNull($submission->supervisor_consent_pdf_path);
        $this->assertNotNull($submission->coordinator_consent_pdf_path);

        $this->actingAs($this->student)
            ->get(route('student.presentation-consent.pdf', $submission))
            ->assertOk();
    }

    #[Test]
    public function coordinator_can_return_consent_for_revision_and_clear_signatures(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for consent PDF generation.');
        }

        $submission = $this->supervisorSignConsent($this->createPendingConsentSubmission());

        $this->actingAs($this->coordinator)
            ->post(route('coordinator.submissions.consent.review', $submission), [
                'decision' => 'needs_revision',
                'comments' => 'Coordinator records show a different group number.',
            ])
            ->assertRedirect(route('coordinator.submissions'))
            ->assertSessionHas('status');

        $submission->refresh();

        $this->assertSame('needs_revision', $submission->status);
        $this->assertFalse((bool) $submission->submitted_to_coordinator);
        $this->assertNull($submission->supervisor_consent_signed_at);
        $this->assertNull($submission->coordinator_approved_at);
        $this->assertDatabaseHas('submission_feedback', [
            'project_submission_id' => $submission->id,
            'decision' => 'needs_revision',
        ]);
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: ProjectGroup}
     */
    private function createConsentWorkspace(): array
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

        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
            'name' => 'Coordinator One',
        ]);

        $supervisor = User::factory()->supervisor()->create([
            'must_change_password' => false,
            'name' => 'Dr. Jane Supervisor',
        ]);

        $student = User::factory()->student('project_student')->create([
            'must_change_password' => false,
            'programme' => 'BBICT',
            'year_of_study' => 3,
            'enrollment_status' => 'active',
            'account_status' => 'active',
        ]);

        Student::factory()->create([
            'user_id' => $student->id,
            'programme_id' => $programme->id,
            'department_id' => $department->id,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);

        $group = ProjectGroup::factory()->create([
            'coordinator_id' => $coordinator->id,
            'name' => 'BBICT/G-101',
        ]);
        $group->members()->sync([$student->id]);

        SupervisorAssignment::factory()->create([
            'project_group_id' => $group->id,
            'supervisor_id' => $supervisor->id,
        ]);

        return [$student->fresh(), $supervisor, $coordinator, $group];
    }

    private function consentStage(): ProjectStage
    {
        return ProjectStage::query()
            ->where('stage_name', RepositoryPublication::consentStageName())
            ->firstOrFail();
    }

    private function createPendingConsentSubmission(): ProjectSubmission
    {
        return ProjectSubmission::query()->create([
            'student_id' => $this->student->id,
            'project_group_id' => $this->group->id,
            'stage' => RepositoryPublication::consentStageName(),
            'title' => 'Final presentation consent',
            'version' => 1,
            'presentation_date' => now()->addWeek(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    private function supervisorSignConsent(ProjectSubmission $submission): ProjectSubmission
    {
        $this->actingAs($this->supervisor)
            ->post(route('supervisor.presentation-consent.sign.store', $submission), [
                'consent_agreed' => '1',
                'signature' => $this->fakeSignatureDataUri(),
                'presentation_date' => $submission->presentation_date?->toDateString() ?? now()->addWeek()->toDateString(),
                'consent_project_title' => 'Smart Campus Navigation System',
                'consent_group_number' => $this->group->name,
            ])
            ->assertRedirect(route('supervisor.index'));

        return $submission->fresh();
    }

    private function approveThroughProgressPresentations(int $count): void
    {
        $this->approveStage('Proposal Chapter 1');
        $this->approveStage('Proposal Chapter 2');
        $this->approveStage('Proposal Chapter 3');
        $this->approveStage('Complete Proposal Document');
        $this->approveStage(StudentStageProgress::completeSystemStageName());

        foreach (range(1, $count) as $index) {
            $this->approveStage('Progress Presentation '.$index);
        }
    }

    private function approveStage(string $stageName): void
    {
        ProjectSubmission::query()->create([
            'student_id' => $this->student->id,
            'project_group_id' => $this->group->id,
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

    private function fakeSignatureDataUri(): string
    {
        $image = imagecreatetruecolor(120, 40);
        $background = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, 119, 39, $background);
        imagearc($image, 60, 25, 90, 30, 0, 180, $ink);

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
