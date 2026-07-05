<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use App\Support\StudentGenderNormalizer;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps `staff` rows aligned with supervisor / coordinator / HoD users so
 * coordinator tools (e.g. supervisor picker) can query Staff.
 */
final class StaffProfileProvisioner
{
    /** @return list<string> */
    public static function staffProfileRoles(): array
    {
        return ['supervisor', 'coordinator', 'hod'];
    }

    public static function userNeedsStaffProfile(User $user): bool
    {
        return in_array((string) $user->role, self::staffProfileRoles(), true);
    }

    public static function resolveDepartmentIdFromLabel(?string $label): ?int
    {
        if ($label === null || trim($label) === '') {
            return null;
        }
        if (! Schema::hasTable('departments')) {
            return null;
        }

        $needle = mb_strtolower(trim($label));

        $dept = Department::query()
            ->whereRaw('LOWER(department_code) = ?', [$needle])
            ->orWhereRaw('LOWER(department_name) = ?', [$needle])
            ->first();

        return $dept?->id;
    }

    public static function syncFromUser(User $user, ?string $gender = null): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        if (! self::userNeedsStaffProfile($user)) {
            Staff::query()->where('user_id', $user->id)->delete();

            return;
        }

        $deptId = self::resolveDepartmentIdFromLabel($user->department);
        $genderNorm = StudentGenderNormalizer::normalize($gender ?? $user->gender);

        $attributes = [
            'staff_number' => $user->login_id,
            'full_name' => $user->name,
            'email' => $user->email,
            'department_id' => $deptId,
            'is_active' => $user->account_status === 'active',
        ];

        if ($genderNorm !== null && Schema::hasColumn('staff', 'gender')) {
            $attributes['gender'] = $genderNorm;
        }

        Staff::query()->updateOrCreate(
            ['user_id' => $user->id],
            $attributes
        );
    }

    /**
     * Upsert `staff` rows for every active supervisor so the coordinator
     * supervisor dropdown stays aligned with `users`.
     */
    public static function syncAllSupervisorStaffProfiles(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        User::query()
            ->where('role', 'supervisor')
            ->where('account_status', 'active')
            ->orderBy('id')
            ->chunkById(150, function ($users): void {
                foreach ($users as $user) {
                    self::syncFromUser($user);
                }
            });
    }
}
