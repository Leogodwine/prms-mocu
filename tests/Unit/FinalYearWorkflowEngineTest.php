<?php

namespace Tests\Unit;

use App\Enums\ProgramOutputType;
use App\Enums\StudentWorkflowRole;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\FinalYearWorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalYearWorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_ict_bachelor_in_final_year_gets_both_tracks_when_allowed(): void
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
            'output_type' => ProgramOutputType::BothAllowed->value,
            'is_project_eligible' => true,
            'project_year' => 3,
        ]);

        $user = User::factory()->create([
            'role' => 'normal_student',
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

        $this->assertTrue(FinalYearWorkflowEngine::isFinalYearEligible($user));
        $this->assertSame(StudentWorkflowRole::FinalYearStudent, FinalYearWorkflowEngine::determineWorkflowRole($user));
        $this->assertEquals(['proposal', 'research', 'project'], FinalYearWorkflowEngine::availableTracks($user));
    }

    public function test_education_bachelor_gets_research_track_only(): void
    {
        $department = Department::factory()->create(['department_code' => 'EDU']);
        $programme = Program::factory()->create([
            'department_id' => $department->id,
            'programme_code' => 'BED',
            'final_year' => 3,
            'output_type' => ProgramOutputType::ResearchOnly->value,
            'is_project_eligible' => false,
        ]);

        $user = User::factory()->create([
            'role' => 'normal_student',
            'programme' => 'BED',
            'year_of_study' => 3,
            'account_status' => 'active',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'programme_id' => $programme->id,
            'year_of_study' => 3,
            'enrollment_status' => 'active',
        ]);

        $this->assertSame(StudentWorkflowRole::ResearchCandidate, FinalYearWorkflowEngine::determineWorkflowRole($user));
        $this->assertEquals(['proposal', 'research'], FinalYearWorkflowEngine::availableTracks($user));
    }

    public function test_non_final_year_student_is_viewer_only(): void
    {
        $programme = Program::factory()->create([
            'final_year' => 3,
            'output_type' => ProgramOutputType::BothAllowed->value,
        ]);

        $user = User::factory()->create([
            'role' => 'normal_student',
            'year_of_study' => 2,
            'account_status' => 'active',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'programme_id' => $programme->id,
            'year_of_study' => 2,
            'enrollment_status' => 'active',
        ]);

        $this->assertSame(StudentWorkflowRole::ViewerOnly, FinalYearWorkflowEngine::determineWorkflowRole($user));
        $this->assertSame([], FinalYearWorkflowEngine::availableTracks($user));
        $this->assertNotNull(FinalYearWorkflowEngine::workflowBlockReason($user));
    }

    public function test_suspended_student_has_no_access(): void
    {
        $programme = Program::factory()->create(['final_year' => 3]);

        $user = User::factory()->create([
            'role' => 'normal_student',
            'year_of_study' => 3,
            'account_status' => 'active',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'programme_id' => $programme->id,
            'year_of_study' => 3,
            'enrollment_status' => 'suspended',
        ]);

        $this->assertSame(StudentWorkflowRole::NoAccess, FinalYearWorkflowEngine::determineWorkflowRole($user));
    }
}
