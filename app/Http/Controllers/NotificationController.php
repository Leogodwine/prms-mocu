<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkNotificationIdsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $statusFilter = in_array($request->query('status'), ['unread', 'read'], true)
            ? (string) $request->query('status')
            : 'all';
        $searchQuery = trim((string) $request->query('q', ''));

        $notificationsQuery = $user->notifications()->latest();

        if ($statusFilter === 'unread') {
            $notificationsQuery->whereNull('read_at');
        } elseif ($statusFilter === 'read') {
            $notificationsQuery->whereNotNull('read_at');
        }

        if ($searchQuery !== '') {
            $like = '%'.addcslashes($searchQuery, '%_\\').'%';
            $notificationsQuery->where('data', 'like', $like);
        }

        $stats = [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => $user->readNotifications()->count(),
        ];

        return view('notifications.index', [
            'notifications' => $notificationsQuery->paginate(20)->withQueryString(),
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
            'hasActiveSearch' => $searchQuery !== '',
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($notification->notifiable_id === $request->user()->id, 403);
        $notification->markAsRead();

        $data = $notification->data ?? [];
        $targetRoute = $data['route'] ?? $data['action_url'] ?? route('notifications.index');

        return redirect($targetRoute);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->route('notifications.index')->with('status', 'All notifications marked as read.');
    }

    public function bulkMarkRead(BulkNotificationIdsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $ids = $request->validated()['notification_ids'];

        $marked = $user->notifications()
            ->whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($marked === 0) {
            $owned = $user->notifications()->whereIn('id', $ids)->count();
            if ($owned === 0) {
                return $this->redirectToNotificationsIndex($request)
                    ->withErrors(['notifications' => 'No matching notifications were found.']);
            }

            return $this->redirectToNotificationsIndex($request)
                ->with('status', 'Selected notifications were already marked as read.');
        }

        return $this->redirectToNotificationsIndex($request)
            ->with('status', "Marked {$marked} notification(s) as read.");
    }

    public function bulkDestroy(BulkNotificationIdsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $ids = $request->validated()['notification_ids'];

        $deleted = $user->notifications()->whereIn('id', $ids)->delete();

        if ($deleted === 0) {
            return $this->redirectToNotificationsIndex($request)
                ->withErrors(['notifications' => 'No notifications were cleared.']);
        }

        return $this->redirectToNotificationsIndex($request)
            ->with('status', "Cleared {$deleted} notification(s).");
    }

    private function redirectToNotificationsIndex(?Request $request = null): RedirectResponse
    {
        $params = [];
        if ($request !== null) {
            $status = $request->input('status', $request->query('status'));
            if (in_array($status, ['unread', 'read'], true)) {
                $params['status'] = $status;
            }
            $search = trim((string) $request->input('q', $request->query('q', '')));
            if ($search !== '') {
                $params['q'] = $search;
            }
        }

        return redirect()->route('notifications.index', $params);
    }

    public function destroy(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === (int) $request->user()->id, 403);

        $notification->delete();

        return redirect()->route('notifications.index')->with('status', 'Notification cleared.');
    }
}
