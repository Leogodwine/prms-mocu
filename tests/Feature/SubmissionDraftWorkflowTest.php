<?php

namespace Tests\Feature;

use App\Models\ProjectSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionDraftWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_blank_word_submission_is_created_as_draft(): void
    {
        Storage::fake('public');
        $this->assertFileExists(resource_path('onlyoffice/blank.docx'));

        $student = User::factory()->student()->create();
        $stageId = DB::table('project_stages')->insertGetId([
            'stage_name' => 'Proposal Chapter 1',
            'stage_order' => 1,
            'days_allowed' => 14,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($student)
            ->post(route('student.submissions.create-blank'), [
                'stage_id' => $stageId,
                'title' => 'Untitled Document',
            ])
            ->assertRedirect();

        $submission = ProjectSubmission::query()->first();
        $this->assertNotNull($submission);
        $this->assertSame('draft', $submission->status);
        $this->assertNull($submission->submitted_at);
    }

    public function test_student_can_submit_draft_to_supervisor_for_first_proposal_chapter(): void
    {
        Notification::fake();
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $path = 'word_documents/test-chapter.docx';
        Storage::disk('public')->put($path, 'docx-content');

        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'Proposal Chapter 1',
            'title' => 'Chapter draft',
            'version' => 1,
            'file_path' => $path,
            'original_filename' => 'chapter.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 12,
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        $this->actingAs($student)
            ->post(route('student.submissions.submit-to-supervisor', $submission))
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame('pending', $submission->status);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_student_cannot_submit_research_draft_without_proposal_completion(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $path = 'word_documents/test-chapter.docx';
        Storage::disk('public')->put($path, 'docx-content');

        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'Research Chapter 1',
            'title' => 'Chapter draft',
            'version' => 1,
            'file_path' => $path,
            'original_filename' => 'chapter.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 12,
            'status' => 'draft',
            'submitted_at' => null,
        ]);

        $this->actingAs($student)
            ->post(route('student.submissions.submit-to-supervisor', $submission))
            ->assertSessionHasErrors('error');
    }
}
