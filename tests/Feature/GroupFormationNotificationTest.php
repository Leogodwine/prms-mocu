<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProjectGroup;
use App\Models\Student;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GroupFormationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_manual_group_creation_notifies_students_coordinator_and_supervisor_on_assignment(): void
    {
        Notification::fake();

        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
            'notify_email_workflow' => true,
            'notify_sms_workflow' => true,
            'phone_number' => '+255700000010',
        ]);

        $supervisor = User::factory()->supervisor()->create([
            'must_change_password' => false,
            'notify_email_workflow' => true,
            'notify_sms_workflow' => true,
            'phone_number' => '+255700000011',
        ]);

        $programme = Program::factory()->create([
            'final_year' => 3,
            'output_type' => 'RESEARCH_ONLY',
        ]);

        $studentA = Student::factory()->create(['programme_id' => $programme->id, 'year_of_study' => 3]);
        $studentB = Student::factory()->create(['programme_id' => $programme->id, 'year_of_study' => 3]);

        $studentA->user->forceFill([
            'must_change_password' => false,
            'notify_email_workflow' => true,
            'notify_sms_workflow' => true,
            'phone_number' => '+255700000012',
        ])->save();

        $studentB->user->forceFill([
            'must_change_password' => false,
            'notify_email_workflow' => true,
            'notify_sms_workflow' => true,
            'phone_number' => '+255700000013',
        ])->save();

        $this->actingAs($coordinator)->post(route('coordinator.groups.store'), [
            'formation_type' => 'group',
            'name' => 'BBICT/2026/G01',
            'student_ids' => [$studentA->user_id, $studentB->user_id],
        ])->assertRedirect(route('coordinator.index'));

        Notification::assertSentTo($studentA->user, WorkflowNotification::class);
        Notification::assertSentTo($studentB->user, WorkflowNotification::class);
        Notification::assertSentTo($coordinator, WorkflowNotification::class);

        $group = ProjectGroup::query()->where('name', 'BBICT/2026/G01')->firstOrFail();

        $this->actingAs($coordinator)->post(route('coordinator.supervisor.assign'), [
            'project_group_id' => $group->id,
            'supervisor_id' => $supervisor->id,
        ])->assertRedirect(route('coordinator.index'));

        Notification::assertSentTo($supervisor, WorkflowNotification::class);
        Notification::assertSentToTimes($coordinator, WorkflowNotification::class, 2);
        Notification::assertSentToTimes($studentA->user, WorkflowNotification::class, 2);
        Notification::assertSentToTimes($studentB->user, WorkflowNotification::class, 2);
    }

    public function test_workflow_notification_toast_payload_is_success_only(): void
    {
        $student = User::factory()->student()->create();

        $student->notify(new WorkflowNotification(
            'Project group created — TEST/G01',
            'You have been placed in project group TEST/G01.',
            route('student.index'),
            'Open student workspace',
            'info'
        ));

        $infoNotification = $student->notifications()->first();
        $this->assertNotNull($infoNotification);
        $this->assertFalse($infoNotification->data['toast'] ?? true);

        $student->notify(new WorkflowNotification(
            'Supervisor assigned — TEST/G01',
            'Dr. Example is now your supervisor.',
            route('student.index'),
            'Open student workspace',
            'success'
        ));

        $successNotification = $student->notifications()->latest()->first();
        $this->assertNotNull($successNotification);
        $this->assertTrue($successNotification->data['toast'] ?? false);
        $this->assertSame('success', $successNotification->data['toast_type'] ?? null);
    }
}
