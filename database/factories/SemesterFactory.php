<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'semester_name' => fake()->randomElement(['Semester I', 'Semester II', 'Annual']),
            'semester_number' => fake()->numberBetween(1, 2),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addMonths(5)->endOfMonth(),
            'is_current' => false,
        ];
    }
}
