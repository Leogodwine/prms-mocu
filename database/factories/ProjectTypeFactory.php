<?php

namespace Database\Factories;

use App\Models\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectType>
 */
class ProjectTypeFactory extends Factory
{
    protected $model = ProjectType::class;

    public function definition(): array
    {
        return [
            'type_name' => fake()->unique()->randomElement([
                'Undergraduate dissertation',
                'Computer-based project',
                'Research thesis',
                'Capstone project',
            ]),
            'description' => fake()->sentence(10),
            'min_students' => 1,
            'max_students' => fake()->randomElement([1, 3, 5]),
            'is_group_based' => true,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $_) => [
            'min_students' => 1,
            'max_students' => 1,
            'is_group_based' => false,
        ]);
    }
}
