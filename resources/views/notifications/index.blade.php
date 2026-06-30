@extends('layouts.app')

@section('title', 'Notifications')

@section('content')

@php
    $isAllNotifications = ($statusFilter ?? 'all') === 'all';
    $isUnreadNotifications = ($statusFilter ?? 'all') === 'unread';
    $isReadNotifications = ($statusFilter ?? 'all') === 'read';
@endphp

<x-prms-greeting-banner subtitle="New uploads, review decisions, and email preferences for your account.">
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3" role="group" aria-label="Notification counts">
        <a href="{{ route('notifications.index') }}"
               class="prms-notif-stat-badge prms-notif-stat-badge--all @if ($isAllNotifications) is-active @endif"
               @if ($isAllNotifications) aria-current="page" @endif>
                All
                <span class="badge rounded-pill prms-notif-stat-badge__count">{{ number_format($stats['total']) }}</span>
            </a>
            <a href="{{ route('notifications.index', ['status' => 'unread']) }}"
               class="prms-notif-stat-badge prms-notif-stat-badge--unread @if ($isUnreadNotifications) is-active @endif"
               @if ($isUnreadNotifications) aria-current="page" @endif>
                New / unread
                <span class="badge rounded-pill prms-notif-stat-badge__count">{{ number_format($stats['unread']) }}</span>
            </a>
            <a href="{{ route('notifications.index', ['status' => 'read']) }}"
               class="prms-notif-stat-badge prms-notif-stat-badge--read @if ($isReadNotifications) is-active @endif"
               @if ($isReadNotifications) aria-current="page" @endif>
                Read
                <span class="badge rounded-pill prms-notif-stat-badge__count">{{ number_format($stats['read']) }}</span>
            </a>
        </div>

        <form action="{{ route('notifications.read-all') }}" method="POST" class="m-0 flex-shrink-0">
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
        <h2 class="h6 fw-bold text-strong mb-3">
            @if ($isUnreadNotifications)
                New / unread
            @elseif ($isReadNotifications)
                Read notifications
            @else
                Recent activity
            @endif
        </h2>

        <div class="card">
            <div id="prmsBulkNotifActions" class="d-none align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 border-bottom bg-surface-soft">
                <span class="small text-muted">
                    <span id="prmsSelectedNotifCount">0</span> selected
                </span>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button"
                            id="prmsBulkMarkReadBtn"
                            class="btn btn-sm btn-outline-primary"
                            disabled>
                        <i class="fas fa-check me-1" aria-hidden="true"></i>
                        Mark read
                    </button>
                    <button type="button"
                            id="prmsBulkDeleteNotifBtn"
                            class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#bulkDeleteNotificationsModal"
                            disabled>
                        <i class="fas fa-trash-alt me-1" aria-hidden="true"></i>
                        Clear selected
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @if ($notifications->isNotEmpty())
                        <div class="list-group-item py-2 bg-white border-bottom">
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input" id="prmsSelectAllNotifications" aria-label="Select all notifications on this page">
                                <label class="form-check-label small text-muted" for="prmsSelectAllNotifications">Select all on this page</label>
                            </div>
                        </div>
                    @endif
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
                                <div class="pt-1 flex-shrink-0">
                                    <input type="checkbox"
                                           class="form-check-input prms-notif-select"
                                           value="{{ $notification->id }}"
                                           aria-label="Select notification: {{ $title }}">
                                </div>
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
                                        <div class="d-flex align-items-center gap-2">
                                            @if (!$isRead)
                                                <form action="{{ route('notifications.read', $notification) }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link btn-sm p-0">Mark read</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="m-0"
                                                  onsubmit="return confirm('Clear this notification?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-danger">Clear</button>
                                            </form>
                                        </div>
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
                            <h3 class="h6 fw-bold text-strong">
                                @if ($isUnreadNotifications)
                                    No unread notifications
                                @elseif ($isReadNotifications)
                                    No read notifications yet
                                @else
                                    You're all caught up
                                @endif
                            </h3>
                            <p class="text-muted small mb-0">
                                @if ($isUnreadNotifications)
                                    You have no new notifications right now.
                                @elseif ($isReadNotifications)
                                    Notifications you mark read or open will appear here.
                                @else
                                    No notifications right now. We'll let you know as things happen.
                                @endif
                            </p>
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

<form id="prmsBulkMarkReadForm" action="{{ route('notifications.bulk-read') }}" method="POST" class="d-none">
    @csrf
</form>

<div class="modal fade" id="bulkDeleteNotificationsModal" tabindex="-1" aria-labelledby="bulkDeleteNotificationsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-surface-soft">
                <h2 class="modal-title h5 fw-bold text-strong" id="bulkDeleteNotificationsModalTitle" style="color: var(--prms-color-danger-500);">
                    <i class="fas fa-trash-alt me-2" aria-hidden="true"></i>
                    Clear selected notifications
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="prmsBulkDeleteNotifForm" method="POST" action="{{ route('notifications.bulk-delete') }}">
                @csrf
                <div class="modal-body p-4">
                    <p class="mb-2">
                        You are about to permanently clear <strong id="prmsBulkDeleteNotifCount">0</strong> notification(s).
                    </p>
                    <p class="text-muted small mb-3">This cannot be undone.</p>
                    <div id="prmsBulkDeleteNotifList" class="small bg-surface-soft rounded p-3 mb-0" style="max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1" aria-hidden="true"></i>
                        Yes, clear selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .prms-notif-stat-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--prms-text-muted);
        text-decoration: none;
        transition: color 0.15s ease;
    }
    .prms-notif-stat-badge:hover {
        color: var(--prms-color-neutral-900);
    }
    .prms-notif-stat-badge .prms-notif-stat-badge__count {
        min-width: 1.65rem;
        padding: 0.35em 0.55em;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .prms-notif-stat-badge--all .prms-notif-stat-badge__count {
        background: var(--prms-color-neutral-200, #e9ecef);
        color: var(--prms-color-neutral-700, #495057);
    }
    .prms-notif-stat-badge--unread .prms-notif-stat-badge__count {
        background: var(--prms-musaris-danger, var(--prms-color-danger-500));
        color: #fff;
    }
    .prms-notif-stat-badge--read .prms-notif-stat-badge__count {
        background: var(--prms-color-success-500);
        color: #fff;
    }
    .prms-notif-stat-badge.is-active {
        color: var(--prms-musaris-emphasis, var(--prms-color-primary-500));
    }
    .prms-notif-stat-badge.is-active .prms-notif-stat-badge__count {
        box-shadow: 0 0 0 2px rgba(var(--prms-primary-rgb, 21, 114, 232), 0.28);
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const selectAll = document.getElementById('prmsSelectAllNotifications');
        const checkboxes = Array.from(document.querySelectorAll('.prms-notif-select'));
        const bulkActions = document.getElementById('prmsBulkNotifActions');
        const selectedCountEl = document.getElementById('prmsSelectedNotifCount');
        const markReadBtn = document.getElementById('prmsBulkMarkReadBtn');
        const deleteBtn = document.getElementById('prmsBulkDeleteNotifBtn');
        const markReadForm = document.getElementById('prmsBulkMarkReadForm');
        const deleteForm = document.getElementById('prmsBulkDeleteNotifForm');
        const deleteModal = document.getElementById('bulkDeleteNotificationsModal');
        const deleteCountEl = document.getElementById('prmsBulkDeleteNotifCount');
        const deleteListEl = document.getElementById('prmsBulkDeleteNotifList');

        function selectedCheckboxes() {
            return checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });
        }

        function notificationTitle(checkbox) {
            const item = checkbox.closest('.list-group-item');
            const titleEl = item ? item.querySelector('h3') : null;
            return titleEl ? titleEl.textContent.trim() : ('Notification ' + checkbox.value);
        }

        function syncSelection() {
            const selected = selectedCheckboxes();
            const count = selected.length;

            if (bulkActions) {
                bulkActions.classList.toggle('d-none', count === 0);
                bulkActions.classList.toggle('d-flex', count > 0);
            }
            if (selectedCountEl) {
                selectedCountEl.textContent = String(count);
            }
            if (markReadBtn) {
                markReadBtn.disabled = count === 0;
            }
            if (deleteBtn) {
                deleteBtn.disabled = count === 0;
            }
            if (selectAll && checkboxes.length > 0) {
                selectAll.checked = count > 0 && count === checkboxes.length;
                selectAll.indeterminate = count > 0 && count < checkboxes.length;
            }
        }

        function appendSelectedIds(form) {
            form.querySelectorAll('input[name="notification_ids[]"]').forEach(function (input) {
                input.remove();
            });

            selectedCheckboxes().forEach(function (checkbox) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'notification_ids[]';
                hidden.value = checkbox.value;
                form.appendChild(hidden);
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncSelection);
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                syncSelection();
            });
        }

        if (markReadBtn && markReadForm) {
            markReadBtn.addEventListener('click', function () {
                appendSelectedIds(markReadForm);
                markReadForm.submit();
            });
        }

        if (deleteModal && deleteForm) {
            deleteModal.addEventListener('show.bs.modal', function () {
                appendSelectedIds(deleteForm);

                const selected = selectedCheckboxes();
                if (deleteCountEl) {
                    deleteCountEl.textContent = String(selected.length);
                }
                if (deleteListEl) {
                    deleteListEl.innerHTML = '';
                    selected.forEach(function (checkbox) {
                        const item = document.createElement('div');
                        item.className = 'mb-1';
                        item.textContent = notificationTitle(checkbox);
                        deleteListEl.appendChild(item);
                    });
                }
            });
        }

        syncSelection();
    })();
</script>
@endpush
