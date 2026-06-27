<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\ProjectGroup;
use App\Models\ProjectType;
use App\Models\ResearchProject;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicResearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_research_index_loads_with_filters(): void
    {
        // 1. Create a ProjectType
        $projectType = ProjectType::factory()->create([
            'type_name' => 'group_project',
        ]);

        // 2. Create a Department, Faculty, Program
        $faculty = Faculty::factory()->create();
        $department = Department::factory()->create(['faculty_id' => $faculty->id]);
        $program = Program::factory()->create(['department_id' => $department->id]);

        // 3. Create a student profile (which automatically creates the user and sets the name/reg number)
        $studentProfile = Student::factory()->create([
            'full_name' => 'John Doe',
            'programme_id' => $program->id,
        ]);
        $studentUser = $studentProfile->user;

        // 4. Create a Group
        $group = ProjectGroup::factory()->create();
        $group->members()->attach($studentUser);

        // 5. Create a ResearchProject
        $project = ResearchProject::factory()->create([
            'title' => 'Sample Research',
            'abstract' => 'This is a sample research abstract.',
            'student_id' => $studentUser->id,
            'project_group_id' => $group->id,
            'project_type_id' => $projectType->id,
            'project_type' => 'research',
            'is_public' => true,
            'published_at' => now()->subDay(),
        ]);

        // Access the public research route with filters
        $response = $this->get('/research?author=John&department_id=' . $department->id . '&search=Sample&sort=recent&type=research');

        $response->assertStatus(200);
        $response->assertSee('Sample Research');

        // Check relationship access from the model
        $this->assertEquals('group_project', $project->projectType->type_name);
        $this->assertEquals($group->id, $project->projectGroup->id);
    }
}
