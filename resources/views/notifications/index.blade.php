@extends('layouts.app')

@section('title', 'Notifications')

@section('content')

<x-prms-greeting-banner subtitle="New uploads, review decisions, and email preferences for your account.">
    <form action="{{ route('notifications.read-all') }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-check-double me-1 text-primary" aria-hidden="true"></i>
            Mark all read
        </button>
    </form>
</x-prms-greeting-banner>

<div class="row g-4">
    {{-- ────────── Notification feed ────────── --}}
    <div class="col-lg-8">
        <h2 class="h6 fw-bold text-strong mb-3">Recent activity</h2>

        <div class="card">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse ($notifications as $notification)
                        @php
                            $isRead = (bool) $notification->read_at;
                            $title = $notification->data['title'] ?? 'System update';
                            $msg = $notification->data['message'] ?? 'Open the dashboard for details.';
                            $actionUrl = $notification->data['route'] ?? ($notification->data['action_url'] ?? null);
                            $actionLabel = $notification->data['action_text'] ?? 'Open';

                            $iconClass = 'far fa-bell';
                            $iconBg = 'bg-info-soft';
                            $iconColor = 'color: var(--prms-color-info-500);';
                            $titleLower = strtolower($title);
                            if (str_contains($titleLower, 'approve')) {
                                $iconClass = 'fas fa-check-circle';
                                $iconBg = 'bg-success-soft';
                                $iconColor = 'color: var(--prms-color-success-500);';
                            } elseif (str_contains($titleLower, 'reject') || str_contains($titleLower, 'fail')) {
                                $iconClass = 'fas fa-times-circle';
                                $iconBg = 'bg-danger-soft';
                                $iconColor = 'color: var(--prms-color-danger-500);';
                            } elseif (str_contains($titleLower, 'registered') || str_contains($titleLower, 'idea')) {
                                $iconClass = 'fas fa-lightbulb';
                                $iconBg = 'bg-primary-soft';
                                $iconColor = 'color: var(--prms-color-primary-500);';
                            }
                        @endphp

                        <div class="list-group-item {{ $isRead ? '' : 'bg-surface-soft' }}">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0 {{ $iconBg }}"
                                     style="width: 40px; height: 40px; {{ $iconColor }}">
                                    <i class="{{ $iconClass }}" aria-hidden="true"></i>
                                </div>

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                        <h3 class="h6 fw-bold mb-0 {{ $isRead ? 'text-muted' : 'text-strong' }}">
                                            {{ $title }}
                                        </h3>
                                        @if (!$isRead)
                                            <span class="badge bg-primary">New</span>
                                        @endif
                                    </div>

                                    <p class="text-muted small mb-2">{{ $msg }}</p>
                                    @if ($actionUrl)
                                        <p class="mb-2">
                                            <a href="{{ $actionUrl }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                                {{ $actionLabel }} <i class="fas fa-arrow-right ms-1 small" aria-hidden="true"></i>
                                            </a>
                                        </p>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                        <span>
                                            <i class="far fa-clock me-1" aria-hidden="true"></i>
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                        @if (!$isRead)
                                            <form action="{{ route('notifications.read', $notification) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm p-0">Mark read</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-5 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                 style="width: 64px; height: 64px;">
                                <i class="far fa-bell-slash text-muted" aria-hidden="true" style="font-size: 1.4rem;"></i>
                            </div>
                            <h3 class="h6 fw-bold text-strong">You're all caught up</h3>
                            <p class="text-muted small mb-0">No new notifications right now. We'll let you know as things happen.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            @if ($notifications->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ────────── Preferences ────────── --}}
    <div class="col-lg-4">
        <h2 class="h5 fw-bold text-strong mb-3">Email preferences</h2>

        <div class="card">
            <div class="card-body">
                <p class="text-muted small mb-4">
                    Choose which emails we send. In-app notifications are always available on this page.
                </p>

                <form action="{{ route('notifications.preferences.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="notif_new"
                               name="notify_email_new_submission" value="1"
                               @checked(auth()->user()->notify_email_new_submission)>
                        <label class="form-check-label fw-semibold" for="notif_new">
                            New student uploads
                        </label>
                        <div class="text-muted small">
                            Email when a student in your group submits work for review.
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" class="form-check-input" id="notif_reviewed"
                               name="notify_email_submission_reviewed" value="1"
                               @checked(auth()->user()->notify_email_submission_reviewed)>
                        <label class="form-check-label fw-semibold" for="notif_reviewed">
                            Review decisions
                        </label>
                        <div class="text-muted small">
                            Email when a supervisor approves or returns your submission.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1" aria-hidden="true"></i>
                        Save preferences
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
