@extends('layouts.app')

@section('title', 'Audit & security')

@push('styles')
    <style>
        .prms-audit-panel-scroll {
            max-height: min(70vh, 560px);
            overflow-y: auto;
        }

        .prms-audit-trail-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--bs-card-bg, #fff);
            box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.075);
        }
    </style>
@endpush

@section('content')
    <x-prms-greeting-banner subtitle="System activity and authentication events.">
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="row g-4 align-items-stretch">
        <div class="col-lg-8 order-lg-1">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-history text-primary me-2" aria-hidden="true"></i>
                        Activity trail
                    </h3>
                </div>
                <x-prms-table-pagination-toolbar :paginator="$auditLogs" noun="events" />
                <div class="table-responsive prms-audit-panel-scroll prms-audit-trail-scroll">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">When</th>
                                <th scope="col">Action</th>
                                <th scope="col">Actor</th>
                                <th scope="col">Target</th>
                                <th scope="col">Summary</th>
                                <th scope="col" class="d-none d-lg-table-cell">IP address</th>
                                <th scope="col" class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($auditLogs as $log)
                                @php
                                    $tone = \App\Support\AuditTrailInterpreter::actionTone($log->action);
                                    $summary = \App\Support\AuditTrailInterpreter::summarize($log);
                                    $entity = \App\Support\AuditTrailInterpreter::entityLabel($log->entity_type, $log->entity_id);
                                    $hasDetails = ! empty($log->new_values) || ! empty($log->old_values);
                                @endphp
                                <tr>
                                    <td class="text-nowrap">
                                        <div class="small fw-semibold text-strong">{{ $log->created_at->format('M j, Y') }}</div>
                                        <div class="small text-muted">{{ $log->created_at->format('g:i A') }}</div>
                                        <div class="small text-muted">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $tone }} bg-opacity-10 text-{{ $tone }} border border-{{ $tone }} border-opacity-25">
                                            {{ \App\Support\AuditTrailInterpreter::actionLabel($log->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-strong">{{ \App\Support\AuditTrailInterpreter::actorLabel($log) }}</div>
                                        @if ($email = \App\Support\AuditTrailInterpreter::actorMeta($log))
                                            <div class="small text-muted text-truncate" style="max-width: 14rem;">{{ $email }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($entity)
                                            <span class="small">{{ $entity }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($summary)
                                            <span class="small text-muted">{{ $summary }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        @if ($log->ip_address)
                                            <code class="small">{{ $log->ip_address }}</code>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($hasDetails)
                                            <details class="small text-start d-inline-block">
                                                <summary class="btn btn-sm btn-light border">View</summary>
                                                <div class="mt-2 p-2 bg-light rounded border text-start" style="min-width: 16rem; max-width: 24rem;">
                                                    @if (! empty($log->old_values))
                                                        <div class="fw-semibold mb-1">Before</div>
                                                        <pre class="mb-2 small" style="white-space: pre-wrap;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    @endif
                                                    @if (! empty($log->new_values))
                                                        <div class="fw-semibold mb-1">After</div>
                                                        <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    @endif
                                                </div>
                                            </details>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="far fa-file-alt fs-1 mb-2 d-block opacity-50" aria-hidden="true"></i>
                                        <p class="mb-0 small">No logs in this timeframe.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent border-top py-3">
                    <x-prms-table-pagination-footer :paginator="$auditLogs" />
                </div>
            </div>
        </div>

        <div class="col-lg-4 order-lg-2">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-sign-in-alt text-strong me-2" aria-hidden="true"></i>
                        Authentication stream
                    </h3>
                </div>
                <div class="card-body p-0">
                    <x-prms-table-pagination-toolbar :paginator="$loginHistory" noun="events" />
                    <div class="list-group list-group-flush prms-audit-panel-scroll">
                        @forelse ($loginHistory as $row)
                            <div class="list-group-item p-3 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle p-2 me-3 d-inline-flex align-items-center justify-content-center {{ $row->success ? 'bg-success' : 'bg-danger' }}" style="width: 36px; height: 36px;">
                                        <i class="fas {{ $row->success ? 'fa-check' : 'fa-exclamation' }} text-white small" aria-hidden="true"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="fw-semibold {{ $row->success ? 'text-strong' : 'text-danger' }}">
                                                {{ $row->success ? 'Successful sign-in' : 'Access denied' }}
                                            </span>
                                            <small class="text-muted">{{ $row->login_time?->diffForHumans() }}</small>
                                        </div>
                                        <div class="text-muted mt-1 small">
                                            @if ($row->user)
                                                {{ $row->user->name }}
                                                <span class="text-muted">({{ $row->user->email }})</span>
                                            @else
                                                User ID: {{ $row->user_id ?? 'Unknown' }}
                                            @endif
                                            @if ($row->ip_address)
                                                <span class="d-block">IP: {{ $row->ip_address }}</span>
                                            @endif
                                            @if (!$row->success && $row->failure_reason)
                                                <span class="text-danger d-block">Reason: {{ $row->failure_reason }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-lock fs-1 mb-2 d-block opacity-50" aria-hidden="true"></i>
                                <p class="mb-0 small">No login history.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-3">
                    <x-prms-table-pagination-footer :paginator="$loginHistory" />
                </div>
            </div>
        </div>
    </div>
@endsection
