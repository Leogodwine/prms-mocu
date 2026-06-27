<?php

namespace Database\Factories;

use App\Models\ProjectGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectGroup>
 */
class ProjectGroupFactory extends Factory
{
    protected $model = ProjectGroup::class;

    public function definition(): array
    {
        return [
            'name' => 'Group '.fake()->unique()->bothify('???-####'),
            'coordinator_id' => User::factory()->coordinator(),
            'academic_year' => (int) date('Y'),
        ];
    }
}
