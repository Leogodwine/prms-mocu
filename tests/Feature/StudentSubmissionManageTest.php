<?php

namespace Tests\Feature;

use App\Models\ProjectSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentSubmissionManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_remove_pending_submission(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('submissions/chapter.pdf', 'pdf-content');

        $student = User::factory()->student()->create();
        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'Proposal Chapter 1',
            'title' => 'chapter 01',
            'version' => 1,
            'file_path' => 'submissions/chapter.pdf',
            'original_filename' => 'chapter.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->delete(route('student.submissions.destroy', $submission))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('project_submissions', ['id' => $submission->id]);
        Storage::disk('public')->assertMissing('submissions/chapter.pdf');
    }

    public function test_student_cannot_remove_approved_submission(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'Proposal Chapter 1',
            'title' => 'chapter 01',
            'version' => 1,
            'file_path' => 'submissions/chapter.pdf',
            'original_filename' => 'chapter.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->delete(route('student.submissions.destroy', $submission))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('project_submissions', ['id' => $submission->id]);
    }
}
