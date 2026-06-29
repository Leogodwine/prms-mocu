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
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_BULK_IMPORT = 'bulk_import';

    /**
     * @return array{user_in_app: bool, user_email: bool, admin_in_app: int}
     */
    public static function notify(
        User $newUser,
        string $loginId,
        string $tempPassword,
        ?User $actor = null,
        string $source = self::SOURCE_MANUAL,
    ): array {
        $result = [
            'user_in_app' => false,
            'user_email' => false,
            'admin_in_app' => 0,
        ];

        try {
            $newUser->notify(new AccountCreatedNotification(
                $loginId,
                $tempPassword,
                ['database'],
                $source,
            ));
            $result['user_in_app'] = true;
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $newUser->notify(new AccountCreatedNotification(
                $loginId,
                $tempPassword,
                ['mail'],
                $source,
            ));
            $result['user_email'] = true;
        } catch (\Throwable $e) {
            report($e);
        }

        $actorId = $actor !== null ? (int) $actor->id : null;
        $actorName = $actor?->name;

        User::query()
            ->where('role', 'admin')
            ->where('account_status', 'active')
            ->orderBy('id')
            ->each(function (User $admin) use (
                $newUser,
                $loginId,
                $tempPassword,
                $actorId,
                $actorName,
                $source,
                &$result,
            ): void {
                try {
                    $admin->notify(new AdminNewUserCredentialsNotification(
                        $newUser->name,
                        $newUser->email,
                        $loginId,
                        $tempPassword,
                        $actorName,
                        $actorId,
                        $source,
                        $newUser->isStudentUser(),
                    ));
                    $result['admin_in_app']++;
                } catch (\Throwable $e) {
                    report($e);
                }
            });

        return $result;
    }
}
