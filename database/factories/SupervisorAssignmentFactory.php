<?php

namespace Database\Factories;

use App\Models\ProjectGroup;
use App\Models\SupervisorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupervisorAssignment>
 */
class SupervisorAssignmentFactory extends Factory
{
    protected $model = SupervisorAssignment::class;

    public function definition(): array
    {
        return [
            'supervisor_id' => User::factory()->supervisor(),
            'project_group_id' => ProjectGroup::factory(),
            'student_id' => User::factory()->student(),
        ];
    }
}
