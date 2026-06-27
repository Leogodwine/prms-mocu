<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2024, 2035);

        return [
            'year_name' => $startYear.'/'.($startYear + 1),
            'start_date' => now()->year($startYear)->startOfYear(),
            'end_date' => now()->year($startYear + 1)->endOfYear(),
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $_) => [
            'is_current' => true,
        ]);
    }
}
