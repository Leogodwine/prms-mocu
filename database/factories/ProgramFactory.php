<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'programme_code' => fake()->unique()->regexify('[A-Z]{3,5}[0-9]{2}'),
            'programme_name' => fake()->sentence(3),
            'department_id' => Department::factory(),
            'duration_years' => fake()->numberBetween(3, 4),
            'academic_level' => 'bachelor',
            'final_year' => fake()->numberBetween(3, 4),
            'output_type' => 'RESEARCH_ONLY',
            'workflow_type' => 'standard',
            'is_project_eligible' => true,
            'project_year' => fake()->numberBetween(3, 4),
        ];
    }
}
