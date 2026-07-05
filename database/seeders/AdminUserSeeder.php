<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds the initial system administrator account.
 *
 * Local / testing: fixed username and password (see constants below).
 * Production: random temporary password printed once; must change at first sign-in.
 */
class AdminUserSeeder extends Seeder
{
    private const ADMIN_NAME = 'System Administrator';

    /** Staff sign-in username (university email). */
    private const ADMIN_EMAIL = 'admin@mocu.ac.tz';

    private const ADMIN_LOGIN_ID = 'MoCU/ADMIN/001/26';

    /** Local-only default password — not used in production. */
    private const LOCAL_DEFAULT_PASSWORD = 'password123';

    public function run(): void
    {
        $useLocalDefaults = app()->environment(['local', 'testing']);

        $admin = User::query()->firstOrNew(['email' => self::ADMIN_EMAIL]);
        $isNew = ! $admin->exists;
        $seedPassword = null;

        $admin->fill([
            'name' => self::ADMIN_NAME,
            'login_id' => self::ADMIN_LOGIN_ID,
            'staff_id' => self::ADMIN_LOGIN_ID,
            'role' => 'admin',
            'account_status' => 'active',
            'enrollment_status' => 'active',
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'notify_email_new_submission' => true,
            'notify_email_submission_reviewed' => true,
            'notify_email_workflow' => true,
            'notify_sms_workflow' => true,
        ]);

        if (Schema::hasColumn('users', 'gender') && ! filled($admin->gender)) {
            $admin->gender = 'male';
        }

        if ($isNew) {
            if ($useLocalDefaults) {
                $seedPassword = self::LOCAL_DEFAULT_PASSWORD;
                $admin->password = $seedPassword;
                $admin->must_change_password = false;
            } else {
                $seedPassword = Str::password(12);
                $admin->password = $seedPassword;
                $admin->must_change_password = true;
            }
        }

        $admin->save();

        $adminRole = Role::query()->where('role_name', 'admin')->first();
        if ($adminRole !== null) {
            $admin->roles()->syncWithoutDetaching([
                $adminRole->id => [
                    'assigned_at' => now(),
                    'is_active' => true,
                ],
            ]);
        }

        if ($isNew) {
            $this->command?->info('Admin account created: '.self::ADMIN_EMAIL);

            if ($useLocalDefaults) {
                $this->command?->line('Local sign-in — username: '.self::ADMIN_EMAIL);
                $this->command?->line('Local sign-in — password: '.self::LOCAL_DEFAULT_PASSWORD);
            } else {
                $this->command?->warn('Temporary password (change at first sign-in): '.$seedPassword);
            }

            return;
        }

        $this->command?->info('Admin account ready: '.self::ADMIN_EMAIL);
    }
}
