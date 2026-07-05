<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AccountCreatedNotification;

/**
 * Sends temporary credentials after an administrator resets a user password.
 */
final class AdminPasswordResetNotifier
{
    public const SOURCE = 'password_reset';

    /**
     * @return array{user_in_app: bool, user_email: bool}
     */
    public static function notify(
        User $user,
        string $loginId,
        string $tempPassword,
        ?User $actor = null,
    ): array {
        unset($actor);

        $result = [
            'user_in_app' => false,
            'user_email' => false,
        ];

        try {
            $user->notify(new AccountCreatedNotification(
                $loginId,
                $tempPassword,
                ['database'],
                self::SOURCE,
            ));
            $result['user_in_app'] = true;
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }

        try {
            $user->notify(new AccountCreatedNotification(
                $loginId,
                $tempPassword,
                ['mail'],
                self::SOURCE,
            ));
            $result['user_email'] = true;
        } catch (\Throwable $e) {
            SafeReport::call($e);
        }

        return $result;
    }
}
