<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;

/**
 * Resolves delivery channels for PRMS workflow notifications.
 */
final class PrmsNotificationChannels
{
    /**
     * @return list<string|class-string>
     */
    public static function workflow(User $user): array
    {
        $channels = ['database'];

        if ($user->notify_email_workflow && filled($user->email)) {
            $channels[] = 'mail';
        }

        if ($user->notify_sms_workflow && self::phoneFor($user) !== null) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public static function phoneFor(User $user): ?string
    {
        $phone = trim((string) ($user->phone_number ?? ''));

        if ($phone === '') {
            $phone = trim((string) ($user->studentProfile?->phone_number ?? ''));
        }

        if ($phone === '') {
            $phone = trim((string) ($user->staffProfile?->phone_number ?? ''));
        }

        return $phone !== '' ? $phone : null;
    }
}
