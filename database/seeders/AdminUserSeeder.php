<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Seeds the initial system administrator from environment variables.
 *
 * Required: PRMS_ADMIN_EMAIL, PRMS_ADMIN_PASSWORD (min. 12 characters in production)
 */
class AdminUserSeeder extends Seeder
{
    private const WEAK_DEFAULT_PASSWORD = 'Admin@prms@2026';

    public function run(): void
    {
        $config = config('prms.admin');
        $email = trim((string) ($config['email'] ?? ''));
        $password = (string) ($config['password'] ?? '');

        if ($email === '') {
            throw new RuntimeException('PRMS_ADMIN_EMAIL must be set before seeding the admin account.');
        }

        if ($password === '') {
            throw new RuntimeException('PRMS_ADMIN_PASSWORD must be set before seeding the admin account.');
        }

        if (app()->environment('production')) {
            if ($password === self::WEAK_DEFAULT_PASSWORD) {
                throw new RuntimeException(
                    'Set a strong PRMS_ADMIN_PASSWORD in .env before seeding in production (not the example default).'
                );
            }

            if (strlen($password) < 12) {
                throw new RuntimeException('PRMS_ADMIN_PASSWORD must be at least 12 characters in production.');
            }
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) $config['name'],
                'login_id' => (string) $config['login_id'],
                'staff_id' => (string) $config['staff_id'],
                'role' => 'admin',
                'password' => $password,
                'account_status' => 'active',
                'enrollment_status' => 'active',
                'email_verified_at' => now(),
                'must_change_password' => (bool) $config['must_change_password'],
                'notify_email_new_submission' => true,
                'notify_email_submission_reviewed' => true,
            ]
        );

        $adminRole = Role::query()->where('role_name', 'admin')->first();
        if ($adminRole !== null) {
            $admin->roles()->syncWithoutDetaching([
                $adminRole->id => [
                    'assigned_at' => now(),
                    'is_active' => true,
                ],
            ]);
        }

        $this->command?->info('Admin account ready: '.$admin->email);
    }
}
