<?php

namespace Tests\Feature;

use App\Models\EvaluationRubric;
use App\Models\ProjectGroup;
use App\Models\ProjectSubmission;
use App\Models\StudentEvaluation;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\StudentStageProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorPresentationGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_supervisor_can_save_group_and_individual_presentation_grades(): void
    {
        $this->seed(\Database\Seeders\ProjectStageSeeder::class);

        $coordinator = User::factory()->coordinator()->create();
        $supervisor = User::factory()->supervisor()->create();
        $studentA = User::factory()->student('project_student')->create();
        $studentB = User::factory()->student('project_student')->create();

        $group = ProjectGroup::factory()->create(['coordinator_id' => $coordinator->id, 'name' => 'Team Nova']);
        $group->members()->sync([$studentA->id, $studentB->id]);
        SupervisorAssignment::factory()->create([
            'project_group_id' => $group->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $rubric = EvaluationRubric::factory()->create(['is_system_default' => true]);
        EvaluationRubric::setSystemDefault($rubric);

        $stage = StudentStageProgress::presentationStageNames()[0];
        $submission = ProjectSubmission::query()->create([
            'student_id' => $studentA->id,
            'project_group_id' => $group->id,
            'stage' => $stage,
            'title' => 'Progress presentation slides',
            'version' => 1,
            'file_path' => 'submissions/test.pdf',
            'original_filename' => 'test.pdf',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $scoreRow = [
            ['criterion' => 'Quality', 'score' => 80, 'weight' => 40, 'comments' => null],
            ['criterion' => 'Completeness', 'score' => 70, 'weight' => 35, 'comments' => null],
            ['criterion' => 'Presentation', 'score' => 90, 'weight' => 25, 'comments' => null],
        ];

        $payload = [
            'evaluation_rubric_id' => $rubric->id,
            'status' => 'draft',
            'group' => [
                'scores' => $scoreRow,
                'general_comments' => 'Solid group delivery.',
            ],
            'members' => [
                $studentA->id => ['scores' => $scoreRow, 'general_comments' => 'Lead presenter.'],
                $studentB->id => ['scores' => $scoreRow, 'general_comments' => 'Good support.'],
            ],
        ];

        $response = $this->actingAs($supervisor)->post(
            route('supervisor.evaluate.store', $submission),
            $payload
        );

        $response->assertRedirect(route('supervisor.index'));

        $this->assertDatabaseHas('student_evaluations', [
            'project_submission_id' => $submission->id,
            'evaluator_id' => $supervisor->id,
            'evaluation_scope' => 'group',
            'student_id' => null,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('student_evaluations', [
            'project_submission_id' => $submission->id,
            'evaluator_id' => $supervisor->id,
            'evaluation_scope' => 'individual',
            'student_id' => $studentA->id,
        ]);

        $this->assertDatabaseHas('student_evaluations', [
            'project_submission_id' => $submission->id,
            'evaluator_id' => $supervisor->id,
            'evaluation_scope' => 'individual',
            'student_id' => $studentB->id,
        ]);

        $this->assertSame(3, StudentEvaluation::query()->where('project_submission_id', $submission->id)->count());
    }

    public function test_system_default_rubric_is_only_option_for_supervisors(): void
    {
        $legacy = EvaluationRubric::factory()->create(['name' => 'Legacy scheme']);
        $default = EvaluationRubric::factory()->create(['name' => 'University standard']);
        EvaluationRubric::setSystemDefault($default->fresh());

        $rubrics = EvaluationRubric::forSupervisorGrading();

        $this->assertCount(1, $rubrics);
        $this->assertTrue($rubrics->first()->is_system_default);
        $this->assertSame('University standard', $rubrics->first()->name);
    }
}
