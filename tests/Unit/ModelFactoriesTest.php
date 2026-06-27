<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\EvaluationRubric;
use App\Models\Program;
use App\Models\ProjectGroup;
use App\Models\ProjectType;
use App\Models\ResearchProject;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SupervisorAssignment;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModelFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_factories_persist_related_models(): void
    {
        $department = Department::factory()->create();
        $this->assertDatabaseHas('departments', ['id' => $department->id]);

        $program = Program::factory()->for($department)->create();
        $this->assertSame($department->id, $program->department_id);

        $year = AcademicYear::factory()->create();
        $semester = Semester::factory()->for($year)->create();
        $this->assertSame($year->id, $semester->academic_year_id);

        $projectType = ProjectType::factory()->create();
        $group = ProjectGroup::factory()->create();
        $this->assertDatabaseHas('project_groups', ['id' => $group->id]);

        $research = ResearchProject::factory()->create([
            'project_group_id' => $group->id,
            'project_type_id' => $projectType->id,
        ]);
        $this->assertDatabaseHas('research_projects', ['id' => $research->id]);

        $studentProfile = Student::factory()->create(['programme_id' => $program->id]);
        $this->assertSame(
            $studentProfile->registration_number,
            $studentProfile->fresh()->user->registration_number
        );

        Staff::factory()->create();

        SupervisorAssignment::factory()->create([
            'project_group_id' => $group->id,
        ]);

        SystemConfiguration::factory()->create();

        EvaluationRubric::factory()->create();

        Role::factory()->create(['role_name' => 'test-role-'.uniqid()]);

        $admin = User::factory()->administrator()->create();

        User::factory()->student('project_student')->create();
        User::factory()->supervisor()->create();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
