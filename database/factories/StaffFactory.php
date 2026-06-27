<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->supervisor(),
            'staff_number' => fake()->unique()->regexify('[A-Z]{3}[0-9]{4,6}'),
            'full_name' => fake()->name(),
            'designation' => fake()->randomElement(['Lecturer', 'Senior lecturer', 'Assistant lecturer']),
            'department_id' => Department::factory(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'office_location' => fake()->optional()->streetAddress(),
            'max_students_allowed' => 10,
            'current_student_count' => fake()->numberBetween(0, 5),
            'is_active' => true,
        ];
    }
}
