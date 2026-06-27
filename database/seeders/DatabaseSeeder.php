<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Production bootstrap — mandatory data only.
 *
 * Seeds: RBAC roles/permissions, workflow stages, initial admin account.
 * Does not seed demo users, sample projects, academic structure, or rubrics.
 *
 * Deploy:
 *   php artisan migrate --force
 *   php artisan db:seed --force
 *
 * Local demo data (optional, never on production):
 *   php artisan db:seed --class=DevelopmentSeeder
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ProjectStageSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
