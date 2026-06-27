<?php

namespace Tests\Feature;

use App\Models\ProjectSubmission;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionShowcaseMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_fetch_showcase_meta_for_own_submission(): void
    {
        $studentProfile = Student::factory()->create();
        $student = $studentProfile->user;

        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'project_source_code',
            'title' => 'Distributed system',
            'description' => 'Assignment on distributed systems analysis.',
            'version' => 1,
            'file_path' => 'submissions/sample.zip',
            'original_filename' => 'distributed-system.zip',
            'mime_type' => 'application/zip',
            'screenshot_path' => 'submissions/screenshots/home.png',
            'status' => 'pending',
            'showcase_doc_summary' => 'Overview from uploaded docs.',
            'showcase_doc_significance' => 'Significance from assignment text.',
            'showcase_readme_body' => "# Distributed system\n\nSubmitted version: v1",
            'showcase_archive_tree' => [
                ['name' => 'app', 'type' => 'dir'],
                ['name' => 'README.md', 'type' => 'file'],
            ],
            'showcase_analysis_status' => 'completed',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson(
            route('student.submissions.showcase-meta', $submission)
        );

        $response->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('overview', 'Overview from uploaded docs.')
            ->assertJsonPath('significance', 'Significance from assignment text.')
            ->assertJsonPath('tree.0.name', 'app');
    }

    public function test_showcase_page_hides_live_demo_until_opened(): void
    {
        $studentProfile = Student::factory()->create();
        $student = $studentProfile->user;

        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'project_source_code',
            'title' => 'Distributed system',
            'description' => 'Assignment on distributed systems analysis.',
            'demo_url' => 'https://example.com/demo',
            'version' => 1,
            'file_path' => 'submissions/sample.zip',
            'original_filename' => 'distributed-system.zip',
            'screenshot_path' => 'submissions/screenshots/home.png',
            'status' => 'pending',
            'showcase_analysis_status' => 'completed',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.submissions.showcase', $submission));

        $response->assertOk()
            ->assertSee('data-prms-showcase-demo')
            ->assertSee('prms-showcase-demo-wrap')
            ->assertSee('about:blank')
            ->assertDontSee('src="https://example.com/demo"');

        $this->actingAs($student)
            ->get(route('student.submissions.showcase', ['submission' => $submission, 'open' => 'demo']))
            ->assertOk()
            ->assertSee('revealLiveDemo();');
    }

    public function test_student_can_open_showcase_page(): void
    {
        $studentProfile = Student::factory()->create();
        $student = $studentProfile->user;

        $submission = ProjectSubmission::query()->create([
            'student_id' => $student->id,
            'stage' => 'project_source_code',
            'title' => 'Distributed system',
            'description' => 'Assignment on distributed systems analysis.',
            'version' => 1,
            'file_path' => 'submissions/sample.zip',
            'original_filename' => 'distributed-system.zip',
            'mime_type' => 'application/zip',
            'screenshot_path' => 'submissions/screenshots/home.png',
            'status' => 'pending',
            'showcase_doc_summary' => 'Overview from uploaded docs.',
            'showcase_analysis_status' => 'completed',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.submissions.showcase', $submission))
            ->assertOk()
            ->assertSee('Distributed system')
            ->assertSee('Overview from uploaded docs.');
    }

    public function test_other_students_cannot_fetch_showcase_meta(): void
    {
        $ownerProfile = Student::factory()->create();
        $otherProfile = Student::factory()->create();

        $submission = ProjectSubmission::query()->create([
            'student_id' => $ownerProfile->user->id,
            'stage' => 'project_source_code',
            'title' => 'Private project',
            'version' => 1,
            'file_path' => 'submissions/private.zip',
            'original_filename' => 'private.zip',
            'screenshot_path' => 'submissions/screenshots/home.png',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($otherProfile->user)
            ->getJson(route('student.submissions.showcase-meta', $submission))
            ->assertForbidden();
    }
}
