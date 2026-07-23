<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Optional local/staging demo data — never run on production.
 *
 *   php artisan db:seed --class=DevelopmentSeeder
 *
 * Loads MoCU academic structure plus deterministic QA accounts, groups,
 * deadlines, submissions, rubrics, and calendar data for end-to-end testing.
 * Keep DatabaseSeeder limited to mandatory bootstrap data only.
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DevelopmentSeeder must not run in production.');
        }

        $this->command?->warn('DevelopmentSeeder: loading academic structure + demo test data for local QA.');
        $this->call([
            AcademicStructureSeeder::class,
            DemoTestDataSeeder::class,
        ]);
    }
}
