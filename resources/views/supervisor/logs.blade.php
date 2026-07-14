@extends('layouts.app')

@section('title', 'Research Supervision Management')

@section('content')
    <x-prms-greeting-banner
        title="Research Supervision Management"
        :show-hello="false"
        subtitle="Track all supervision meetings, progress assessments, and follow-up activities.">
        <a href="{{ route('supervisor.logs.create') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-plus me-2" aria-hidden="true"></i> New supervision meeting
        </a>
        <a href="{{ route('supervisor.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i> Review submissions
        </a>
        <a href="{{ route('supervisor.workload') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-user-graduate me-1" aria-hidden="true"></i> Assigned students
        </a>
        <!--
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
-->
    </x-prms-greeting-banner>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="h6 fw-bold text-strong mb-1">Meeting history</h2>
                        <p class="text-muted small mb-0">View and manage all previous supervision sessions, progress updates, and agreed action items.</p>
                    </div>
                    <a href="{{ route('supervisor.logs.create') }}" class="btn btn-primary btn-sm rounded-pill flex-shrink-0">
                        <i class="fas fa-plus me-1" aria-hidden="true"></i> New supervision meeting
                    </a>
                </div>
                <div class="card-body p-0">
                    <x-prms-table-pagination-toolbar :paginator="$logs" noun="meetings" />
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0 supervision-history-table text-start">
                            <thead class="table-light border-bottom">
                                <tr>
                                    <th scope="col" class="supervision-history-th supervision-history-th-date">Date &amp; time</th>
                                    <th scope="col" class="supervision-history-th supervision-history-th-entity">Student / Group</th>
                                    <th scope="col" class="supervision-history-th supervision-history-th-summary">Meeting notes</th>
                                    <th scope="col" class="supervision-history-th supervision-history-th-progress text-center">Progress</th>
                                    <th scope="col" class="supervision-history-th supervision-history-th-actions text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    @php
                                        $startsAt = $log->meeting_starts_at;
                                        $endsAt = $log->meeting_ends_at;
                                        $detailPayload = json_encode([
                                            'meetingDateFormatted' => $startsAt?->format('M d, Y'),
                                            'meetingTimeFormatted' => $startsAt && $endsAt
                                                ? $startsAt->format('H:i').' – '.$endsAt->format('H:i')
                                                : ($startsAt?->format('H:i') ?? ''),
                                            'entityLabel' => $log->project_group_id ? 'Group' : 'Student',
                                            'entityName' => $log->project_group_id
                                                ? (optional($log->projectGroup)->name ?? '—')
                                                : (optional($log->student)->name ?? '—'),
                                            'summary' => (string) ($log->summary ?? ''),
                                            'nextSteps' => (string) ($log->next_steps ?? ''),
                                            'progressScore' => (int) $log->progress_score,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                                    @endphp
                                <tr>
                                    <td class="supervision-history-td supervision-history-td-date">
                                        <span class="fw-semibold text-strong text-nowrap d-block">{{ $startsAt?->format('M d, Y') ?? '—' }}</span>
                                        @if ($startsAt && $endsAt)
                                            <span class="small text-muted text-nowrap">{{ $startsAt->format('H:i') }} – {{ $endsAt->format('H:i') }}</span>
                                        @endif
                                    </td>
                                    <td class="supervision-history-td supervision-history-td-entity">
                                        @if($log->project_group_id)
                                            <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill">
                                                <i class="fas fa-users me-1" aria-hidden="true"></i> {{ $log->projectGroup->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-soft-info text-info px-3 py-2 rounded-pill">
                                                <i class="fas fa-user me-1" aria-hidden="true"></i> {{ $log->student->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="supervision-history-td supervision-history-td-summary">
                                        <p class="mb-0 small text-muted supervision-history-summary">{{ $log->summary }}</p>
                                    </td>
                                    <td class="supervision-history-td supervision-history-td-progress">
                                        <div class="d-flex align-items-center justify-content-center gap-2 flex-nowrap">
                                            <div class="progress supervision-history-progress-bar flex-shrink-0"
                                                 role="progressbar"
                                                 aria-valuenow="{{ (int) $log->progress_score }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100"
                                                 aria-label="Progress {{ $log->progress_score }} percent">
                                                <div class="progress-bar bg-primary" style="width: {{ $log->progress_score }}%"></div>
                                            </div>
                                            <span class="small fw-semibold text-nowrap text-strong">{{ $log->progress_score }}%</span>
                                        </div>
                                    </td>
                                    <td class="supervision-history-td supervision-history-td-actions text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#supervisionLogDetailModal"
                                                data-log-details="{{ e($detailPayload) }}"
                                                title="View meeting details"
                                                aria-label="View details for meeting on {{ $startsAt?->format('M d, Y') ?? 'unknown date' }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">View details</span>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <img src="{{ asset('images/empty-state.svg') }}" alt="" class="mb-3" style="height: 120px; opacity: 0.5;">
                                        <p class="text-muted mb-3">No supervision meetings recorded yet.</p>
                                        <a href="{{ route('supervisor.logs.create') }}" class="btn btn-primary btn-sm rounded-pill">
                                            <i class="fas fa-plus me-1" aria-hidden="true"></i> New supervision meeting
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-transparent border-top py-3">
                        <x-prms-table-pagination-footer :paginator="$logs" />
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="supervisionLogDetailModal" tabindex="-1" aria-labelledby="supervisionLogDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-surface-soft border-bottom">
                <div>
                    <h2 class="modal-title h5 fw-bold text-strong mb-0" id="supervisionLogDetailModalLabel">
                        <i class="fas fa-clipboard-list text-primary me-2" aria-hidden="true"></i>
                        Supervision meeting
                    </h2>
                    <p class="text-muted small mb-0 mt-1" id="sld-meeting-date-line"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted small text-uppercase fw-semibold">Meeting time</dt>
                    <dd class="col-sm-9 mb-3" id="sld-meeting-time-line"></dd>
                    <dt class="col-sm-3 text-muted small text-uppercase fw-semibold">Student / Group</dt>
                    <dd class="col-sm-9 mb-3">
                        <span class="badge bg-light text-dark border me-2" id="sld-entity-label"></span>
                        <span class="fw-semibold text-strong" id="sld-entity-name"></span>
                    </dd>
                    <dt class="col-sm-3 text-muted small text-uppercase fw-semibold">Progress status</dt>
                    <dd class="col-sm-9 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="progress flex-grow-1" style="max-width: 220px; height: 10px;" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-primary" id="sld-progress-bar" style="width: 0%"></div>
                            </div>
                            <span class="fw-semibold" id="sld-progress-label"></span>
                        </div>
                    </dd>
                    <dt class="col-sm-3 text-muted small text-uppercase fw-semibold">Meeting notes</dt>
                    <dd class="col-sm-9 mb-3">
                        <p class="mb-0 text-strong supervision-log-detail-text" id="sld-summary"></p>
                    </dd>
                    <dt class="col-sm-3 text-muted small text-uppercase fw-semibold">Agreed actions</dt>
                    <dd class="col-sm-9">
                        <p class="mb-0 text-muted supervision-log-detail-text" id="sld-next-steps"></p>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const modalEl = document.getElementById('supervisionLogDetailModal');
        if (!modalEl || !window.bootstrap) return;

        const dateLine = document.getElementById('sld-meeting-date-line');
        const timeLine = document.getElementById('sld-meeting-time-line');
        const entityLabel = document.getElementById('sld-entity-label');
        const entityName = document.getElementById('sld-entity-name');
        const progressBar = document.getElementById('sld-progress-bar');
        const progressLabel = document.getElementById('sld-progress-label');
        const summaryEl = document.getElementById('sld-summary');
        const nextStepsEl = document.getElementById('sld-next-steps');
        const progressWrap = progressBar ? progressBar.closest('[role="progressbar"]') : null;

        modalEl.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn || !btn.getAttribute('data-log-details')) return;

            let data;
            try {
                data = JSON.parse(btn.getAttribute('data-log-details'));
            } catch (e) {
                return;
            }

            const pct = Math.max(0, Math.min(100, parseInt(data.progressScore, 10) || 0));

            if (dateLine) {
                dateLine.textContent = data.meetingDateFormatted ? 'Meeting date: ' + data.meetingDateFormatted : '';
            }
            if (timeLine) {
                timeLine.textContent = data.meetingTimeFormatted
                    ? data.meetingTimeFormatted
                    : '—';
            }
            if (entityLabel) entityLabel.textContent = data.entityLabel || '';
            if (entityName) entityName.textContent = data.entityName || '—';
            if (progressBar) progressBar.style.width = pct + '%';
            if (progressLabel) progressLabel.textContent = pct + '%';
            if (progressWrap) {
                progressWrap.setAttribute('aria-valuenow', String(pct));
                progressWrap.setAttribute('aria-label', 'Progress ' + pct + ' percent');
            }
            if (summaryEl) summaryEl.textContent = data.summary || '—';
            if (nextStepsEl) {
                const ns = (data.nextSteps || '').trim();
                nextStepsEl.textContent = ns !== '' ? ns : '—';
            }
        });
    })();
</script>
@endpush

@push('styles')
<style>
    .bg-soft-primary { background-color: var(--prms-primary-soft); }
    .bg-soft-info { background-color: rgba(21, 156, 214, 0.1); }

    .supervision-history-table thead th {
        vertical-align: middle;
    }
    .supervision-history-th {
        padding: 0.75rem 1rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
        white-space: nowrap;
    }
    .supervision-history-th-date { width: 9.5rem; }
    .supervision-history-th-entity { min-width: 11rem; }
    .supervision-history-th-summary { min-width: 14rem; }
    .supervision-history-th-progress { width: 10.5rem; }
    .supervision-history-th-actions { width: 6.5rem; }

    .supervision-history-td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
    }
    .supervision-history-summary {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
        max-width: 28rem;
    }
    .supervision-history-progress-bar {
        height: 8px;
        width: 5.5rem;
        min-width: 4rem;
    }
    .supervision-log-detail-text {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
@endpush
@endsection
