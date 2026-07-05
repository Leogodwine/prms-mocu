<?php

namespace Tests\Feature;

use App\Models\ProjectGroup;
use App\Models\Program;
use App\Models\StageDeadline;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_coordinator_deadlines_page_renders(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($coordinator)->get(route('coordinator.deadlines'));

        $response->assertOk();
        $response->assertSee('Active timelines', false);
    }

    public function test_coordinator_deadlines_page_renders_with_existing_rows(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);

        StageDeadline::query()->create([
            'stage_name' => 'proposal_chapter_1',
            'academic_year' => '2025/2026',
            'start_time' => now()->subDay(),
            'end_time' => now()->addWeek(),
        ]);

        $response = $this->actingAs($coordinator)->get(route('coordinator.deadlines'));

        $response->assertOk();
        $response->assertSee('Proposal Chapter 1', false);
    }

    public function test_coordinator_final_submissions_page_renders(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($coordinator)->get(route('coordinator.submissions'));

        $response->assertOk();
        $response->assertSee('Final submissions', false);
    }

    public function test_coordinator_similar_projects_page_renders(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($coordinator)->get(route('coordinator.similarities.index'));

        $response->assertOk();
        $response->assertSee('Similar projects', false);
        $response->assertDontSee('projects:check-similarities', false);
    }

    public function test_coordinator_cannot_access_admin_similarities_route(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);

        $this->actingAs($coordinator)->get(route('admin.similarities.index'))->assertForbidden();
    }

    public function test_coordinator_assignment_list_only_shows_final_year_students_with_programmes(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);
        $program = Program::factory()->create([
            'programme_code' => 'BBICT',
            'final_year' => 3,
            'output_type' => 'RESEARCH_ONLY',
        ]);

        $finalYear = Student::factory()->create([
            'full_name' => 'Final Year Student',
            'programme_id' => $program->id,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);
        $lowerYear = Student::factory()->create([
            'full_name' => 'Second Year Student',
            'programme_id' => $program->id,
            'year_of_study' => 2,
            'enrollment_status' => 'active',
        ]);
        Student::factory()->create([
            'full_name' => 'Missing Programme Student',
            'programme_id' => null,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);

        $response = $this->actingAs($coordinator)->get(route('coordinator.index', [
            'department_id' => $program->department_id,
        ]));

        $response->assertOk();
        $response->assertSee($finalYear->full_name, false);
        $response->assertDontSee($lowerYear->full_name, false);
        $response->assertDontSee('Missing Programme Student', false);
    }

    public function test_coordinator_cannot_assign_non_final_year_student(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);
        $program = Program::factory()->create([
            'final_year' => 3,
            'output_type' => 'RESEARCH_ONLY',
        ]);
        $lowerYearA = Student::factory()->create([
            'programme_id' => $program->id,
            'year_of_study' => 2,
            'enrollment_status' => 'active',
        ]);
        $lowerYearB = Student::factory()->create([
            'programme_id' => $program->id,
            'year_of_study' => 2,
            'enrollment_status' => 'active',
        ]);

        $response = $this->actingAs($coordinator)->from(route('coordinator.index'))->post(route('coordinator.groups.store'), [
            'formation_type' => 'group',
            'name' => 'Invalid Group',
            'student_ids' => [$lowerYearA->user_id, $lowerYearB->user_id],
        ]);

        $response->assertRedirect(route('coordinator.index'));
        $response->assertSessionHasErrors('student_ids');
        $this->assertDatabaseMissing('project_groups', ['name' => 'Invalid Group']);
    }

    public function test_coordinator_cannot_group_students_from_different_years(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);
        $program = Program::factory()->create([
            'final_year' => 3,
            'output_type' => 'RESEARCH_ONLY',
        ]);
        $thirdYear = Student::factory()->create([
            'programme_id' => $program->id,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);
        $fourthYear = Student::factory()->create([
            'programme_id' => $program->id,
            'year_of_study' => 4,
            'enrollment_status' => 'active',
        ]);

        $response = $this->actingAs($coordinator)->from(route('coordinator.index'))->post(route('coordinator.groups.store'), [
            'formation_type' => 'group',
            'name' => 'Mixed Year Group',
            'student_ids' => [$thirdYear->user_id, $fourthYear->user_id],
        ]);

        $response->assertRedirect(route('coordinator.index'));
        $response->assertSessionHasErrors('student_ids');
        $this->assertDatabaseMissing('project_groups', ['name' => 'Mixed Year Group']);
    }

    public function test_coordinator_can_view_own_group_members_and_supervisor(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);
        $supervisor = User::factory()->supervisor()->create([
            'name' => 'Dr Jane Supervisor',
            'must_change_password' => false,
        ]);
        $student = User::factory()->student()->create([
            'name' => 'Alice Student',
            'registration_number' => 'MoCU/BCS/001/24',
            'must_change_password' => false,
        ]);

        $group = ProjectGroup::factory()->create([
            'name' => 'Alpha Team',
            'coordinator_id' => $coordinator->id,
        ]);
        $group->members()->attach($student->id);

        SupervisorAssignment::factory()->create([
            'project_group_id' => $group->id,
            'supervisor_id' => $supervisor->id,
            'student_id' => null,
        ]);

        $response = $this->actingAs($coordinator)->get(route('coordinator.groups.show', $group));

        $response->assertOk();
        $response->assertSee('Alpha Team', false);
        $response->assertSee('Alice Student', false);
        $response->assertSee('MoCU/BCS/001/24', false);
        $response->assertSee('Dr Jane Supervisor', false);
    }

    public function test_coordinator_cannot_view_another_coordinators_group(): void
    {
        $coordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);
        $otherCoordinator = User::factory()->coordinator()->create([
            'must_change_password' => false,
        ]);

        $group = ProjectGroup::factory()->create([
            'coordinator_id' => $otherCoordinator->id,
        ]);

        $this->actingAs($coordinator)->get(route('coordinator.groups.show', $group))->assertForbidden();
    }
}
