<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'registration_number' => fake()->unique()->bothify('MoCU/REG/####/##'),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(['male', 'female']),
            'programme_id' => Program::factory(),
            'year_of_study' => fake()->numberBetween(2, 4),
            'enrollment_status' => 'active',
            'university_email' => fake()->unique()->safeEmail(),
            'personal_email' => fake()->optional()->safeEmail(),
            'phone_number' => fake()->optional()->phoneNumber(),
            'admission_date' => now()->subMonths(6),
            'expected_graduation' => now()->addYears(2),
            'sis_data' => null,
            'sis_sync_date' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Student $student): void {
            $student->user->forceFill([
                'registration_number' => $student->registration_number,
                'name' => $student->full_name,
            ])->save();
        });
    }
}
