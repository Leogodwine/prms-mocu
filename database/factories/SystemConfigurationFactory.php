<?php

namespace Database\Factories;

use App\Models\SystemConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemConfiguration>
 */
class SystemConfigurationFactory extends Factory
{
    protected $model = SystemConfiguration::class;

    public function definition(): array
    {
        return [
            'config_key' => 'test_'.fake()->unique()->slug(3).'_'.fake()->numerify('##'),
            'config_value' => fake()->word(),
            'config_type' => 'string',
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['general', 'deadlines', 'lifecycle', 'eligibility']),
        ];
    }
}
