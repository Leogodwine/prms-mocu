<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;

/**
 * Resolves delivery channels for PRMS notifications.
 */
final class PrmsNotificationChannels
{
    /**
     * Group formation, supervisor assignment, deadlines, and workflow alerts.
     *
     * @return list<string|class-string>
     */
    public static function workflow(User $user): array
    {
        return self::withOptionalMailAndSms($user, (bool) $user->notify_email_workflow);
    }

    /**
     * Supervisor alert when a student uploads work.
     *
     * @return list<string|class-string>
     */
    public static function submissionAlert(User $user): array
    {
        return self::withOptionalMailAndSms($user, (bool) $user->notify_email_new_submission);
    }

    /**
     * Student alert when a supervisor reviews their submission.
     *
     * @return list<string|class-string>
     */
    public static function reviewAlert(User $user): array
    {
        return self::withOptionalMailAndSms($user, (bool) $user->notify_email_submission_reviewed);
    }

    /**
     * In-app only for students; email for staff when submission prefs are on.
     *
     * @return list<string|class-string>
     */
    public static function projectAlert(User $user): array
    {
        if ($user->isStudentUser()) {
            return ['database'];
        }

        $channels = ['database'];

        if ($user->notify_email_new_submission || $user->notify_email_submission_reviewed) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public static function phoneFor(User $user): ?string
    {
        $candidates = [
            $user->phone_number,
            $user->studentProfile?->phone_number,
            $user->staffProfile?->phone_number,
        ];

        foreach ($candidates as $phone) {
            $normalized = PrmsSms::normalizePhone(is_string($phone) ? $phone : null);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    public static function smsEnabledFor(User $user): bool
    {
        return (bool) $user->notify_sms_workflow
            && self::phoneFor($user) !== null
            && config('prms.sms.enabled', false);
    }

    /**
     * @return list<string|class-string>
     */
    private static function withOptionalMailAndSms(User $user, bool $emailEnabled): array
    {
        $channels = ['database'];

        if ($emailEnabled && filled($user->email)) {
            $channels[] = 'mail';
        }

        if ((bool) $user->notify_sms_workflow && self::phoneFor($user) !== null) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }
}
