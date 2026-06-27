<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $code = fake()->unique()->regexify('[A-Z]{2,4}[0-9]{0,2}');

        return [
            'department_code' => $code,
            'department_name' => fake()->words(2, true).' Department',
            'head_of_department' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
        ];
    }
}
