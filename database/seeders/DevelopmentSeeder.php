<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Optional local/staging demo data — never run on production.
 *
 *   php artisan db:seed --class=DevelopmentSeeder
 *
 * Add demo seeders here (faculty, programmes, test users) as needed for QA.
 * Keep DatabaseSeeder limited to mandatory bootstrap data only.
 */
class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DevelopmentSeeder must not run in production.');
        }

        $this->command?->warn('DevelopmentSeeder: loading academic structure for local QA.');
        $this->call(AcademicStructureSeeder::class);
    }
}
