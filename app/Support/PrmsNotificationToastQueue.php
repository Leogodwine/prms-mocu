<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Queues unread in-app notifications as bottom-right toasts (once per session).
 */
final class PrmsNotificationToastQueue
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function build(Request $request, int $limit = 5): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        /** @var list<string> $shown */
        $shown = $request->session()->get('prms_toast_notification_ids', []);

        $notifications = $user->unreadNotifications()
            ->where('created_at', '>=', now()->subDays(14))
            ->latest()
            ->limit($limit + count($shown))
            ->get()
            ->filter(function ($notification) use ($shown) {
                if (in_array($notification->id, $shown, true)) {
                    return false;
                }

                $data = $notification->data ?? [];

                return ($data['toast'] ?? false) === true
                    && ($data['toast_type'] ?? '') === 'success';
            })
            ->take($limit)
            ->values();

        if ($notifications->isEmpty()) {
            return [];
        }

        $queue = $notifications->map(function ($notification) {
            $data = $notification->data ?? [];

            return [
                'type' => (string) ($data['toast_type'] ?? 'info'),
                'title' => (string) ($data['title'] ?? 'Notification'),
                'message' => (string) ($data['message'] ?? ''),
                'duration' => 9000,
            ];
        })->all();

        $request->session()->put(
            'prms_toast_notification_ids',
            array_slice(array_merge($shown, $notifications->pluck('id')->all()), -120)
        );

        return $queue;
    }
}
