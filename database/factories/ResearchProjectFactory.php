<?php

namespace Database\Factories;

use App\Models\ProjectGroup;
use App\Models\ProjectType;
use App\Models\ResearchProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchProject>
 */
class ResearchProjectFactory extends Factory
{
    protected $model = ResearchProject::class;

    public function definition(): array
    {
        return [
            'project_code' => 'PRJ-'.fake()->unique()->numerify('######'),
            'title' => fake()->sentence(6),
            'abstract' => fake()->paragraph(),
            'student_id' => User::factory()->student(),
            'supervisor_id' => User::factory()->supervisor(),
            'co_supervisor_id' => null,
            'project_group_id' => ProjectGroup::factory(),
            'project_type_id' => ProjectType::factory(),
            'department_id' => null,
            'faculty_id' => null,
            'program_id' => null,
            'academic_year_id' => null,
            'semester_id' => null,
            'project_type' => 'research',
            'status' => 'ongoing',
            'keywords' => implode(', ', fake()->words(5)),
            'research_area' => fake()->words(3, true),
            'current_stage' => 'Draft',
            'submission_deadline' => now()->addMonths(3),
            'plagiarism_score' => null,
            'preview_enabled' => true,
            'collaboration_enabled' => true,
            'final_grade' => null,
            'final_grade_letter' => null,
            'is_archived' => false,
            'archived_date' => null,
            'funding_source' => null,
            'ethical_clearance_number' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $_) => [
            'status' => 'completed',
            'current_stage' => 'Completed',
        ]);
    }
}
