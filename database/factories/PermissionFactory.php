<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'permission_name' => fake()->unique()->slug(3).'_'.fake()->numerify('##'),
            'module' => fake()->randomElement(['projects', 'users', 'reports', 'settings']),
        ];
    }
}
