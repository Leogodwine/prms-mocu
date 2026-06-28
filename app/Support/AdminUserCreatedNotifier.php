<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\AdminNewUserCredentialsNotification;

/**
 * Sends welcome notifications to the new user and in-app credential alerts to all admins.
 */
final class AdminUserCreatedNotifier
{
    public static function notify(User $newUser, string $loginId, string $tempPassword, ?User $actor = null): void
    {
        try {
            $newUser->notify(new AccountCreatedNotification($loginId, $tempPassword));
        } catch (\Throwable $e) {
            report($e);
        }

        $actorId = $actor !== null ? (int) $actor->id : null;
        $actorName = $actor?->name;

        User::query()
            ->where('role', 'admin')
            ->where('account_status', 'active')
            ->orderBy('id')
            ->each(function (User $admin) use ($newUser, $loginId, $tempPassword, $actorId, $actorName): void {
                try {
                    $admin->notify(new AdminNewUserCredentialsNotification(
                        $newUser->name,
                        $newUser->email,
                        $loginId,
                        $tempPassword,
                        $actorName,
                        $actorId,
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }
            });
    }
}
