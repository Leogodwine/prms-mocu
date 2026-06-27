<?php

namespace Database\Factories;

use App\Models\EvaluationRubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationRubric>
 */
class EvaluationRubricFactory extends Factory
{
    protected $model = EvaluationRubric::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true).' grading scheme',
            'description' => fake()->sentence(10),
            'criteria' => [
                ['name' => 'Quality', 'weight' => 40, 'description' => fake()->sentence()],
                ['name' => 'Completeness', 'weight' => 35, 'description' => fake()->sentence()],
                ['name' => 'Presentation', 'weight' => 25, 'description' => fake()->sentence()],
            ],
            'total_marks' => 100,
            'is_active' => true,
        ];
    }
}
