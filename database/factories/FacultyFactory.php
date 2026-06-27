<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 */
class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    public function definition(): array
    {
        return [
            'faculty_code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),
            'faculty_name' => fake()->words(2, true).' Faculty',
            'dean_id' => null,
            'description' => fake()->sentence(8),
            'office_location' => fake()->optional()->streetAddress(),
            'is_active' => true,
        ];
    }
}
