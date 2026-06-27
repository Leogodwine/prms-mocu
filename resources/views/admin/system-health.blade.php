@extends('layouts.app')

@section('title', 'System health')

@section('content')
    <x-prms-greeting-banner subtitle="Queues, jobs, sync, and security snapshot.">
        <form method="POST" action="{{ route('admin.system-health.heartbeat') }}" class="m-0">
            @csrf
            <button class="btn btn-light border rounded-pill px-3" type="submit">
                <i class="fas fa-heartbeat me-1" aria-hidden="true"></i> Heartbeat
            </button>
        </form>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back
        </a>
    </x-prms-greeting-banner>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="prms-stat-card text-center py-3" style="--prms-primary: var(--prms-color-danger-500);">
                <div class="stat-label">Failed jobs</div>
                <div class="stat-value {{ ($queueFailed ?? 0) > 0 ? 'text-danger' : '' }}">{{ $queueFailed ?? '0' }}</div>
                <i class="fas fa-exclamation-triangle text-danger opacity-50 mt-2" aria-hidden="true"></i>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="prms-stat-card text-center py-3" style="--prms-primary: var(--prms-color-success-500);">
                <div class="stat-label">Audit (24h)</div>
                <div class="stat-value">{{ $recentAuditCount }}</div>
                <i class="fas fa-clipboard-list text-success opacity-50 mt-2" aria-hidden="true"></i>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="prms-stat-card text-center py-3" style="--prms-primary: var(--prms-color-warning-500);">
                <div class="stat-label">Login failures (24h)</div>
                <div class="stat-value {{ $recentLoginFailures > 10 ? 'text-danger' : 'text-warning' }}">{{ $recentLoginFailures }}</div>
                <i class="fas fa-unlock-alt text-warning opacity-50 mt-2" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-strong d-flex align-items-center">
                        <i class="fas fa-bug text-danger me-2" aria-hidden="true"></i> Recent job failures
                    </span>
                    @if (($queueFailed ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.system-health.failed-jobs.clear') }}" class="m-0">
                            @csrf
                            <button class="btn btn-sm btn-link text-danger p-0 small" type="submit">Clear all</button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($failedJobs as $job)
                            <div class="list-group-item p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h4 class="h6 fw-bold mb-1">Job #{{ $job->id }}</h4>
                                        <p class="mb-0 text-muted small">
                                            <i class="far fa-clock me-1" aria-hidden="true"></i> {{ $job->failed_at }}
                                            · <i class="fas fa-server me-1" aria-hidden="true"></i> {{ $job->queue }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.system-health.failed-jobs.retry', $job->id) }}" class="m-0">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit">
                                            <i class="fas fa-redo me-1" aria-hidden="true"></i> Retry
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fs-1 mb-2 d-block opacity-25" aria-hidden="true"></i>
                                <p class="mb-0 small">No operational failures detected.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-surface-soft small text-muted">
                    Worker health: {{ $queueHeartbeat ?: 'Unknown' }}
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4 border-start border-4 border-info">
                <div class="card-body">
                    <h3 class="h6 fw-bold text-strong mb-3 d-flex align-items-center">
                        <i class="fas fa-sync text-info me-2" aria-hidden="true"></i> Latest SIS sync
                    </h3>
                    @if ($latestSisSync)
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <span class="badge {{ $latestSisSync->sync_status === 'success' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3">
                                {{ strtoupper($latestSisSync->sync_status) }}
                            </span>
                            <small class="text-muted">{{ $latestSisSync->sync_timestamp?->diffForHumans() }}</small>
                        </div>
                        <div class="mt-3 p-2 bg-surface-soft rounded border-soft small">
                            <div class="d-flex justify-content-between py-1"><span>Processed</span><span class="fw-bold">{{ $latestSisSync->records_processed }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span>Added</span><span class="fw-bold text-success">{{ $latestSisSync->records_added }}</span></div>
                            <div class="d-flex justify-content-between py-1"><span>Updated</span><span class="fw-bold text-info">{{ $latestSisSync->records_updated }}</span></div>
                        </div>
                    @else
                        <p class="text-muted small mb-0">No sync events found.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 fw-bold text-strong mb-3 d-flex align-items-center">
                        <i class="fas fa-gavel text-primary me-2" aria-hidden="true"></i> Recent security
                    </h3>
                    @if ($latestAudit)
                        <p class="mb-1 fw-medium text-strong small">{{ $latestAudit->action }}</p>
                        <small class="text-muted">{{ $latestAudit->created_at->diffForHumans() }}</small>
                    @else
                        <p class="text-muted small mb-0">No audit activity.</p>
                    @endif
                    <hr>
                    @if ($latestLogin)
                        <div class="d-flex align-items-center">
                            <i class="fas {{ $latestLogin->success ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger' }} me-2" aria-hidden="true"></i>
                            <span class="small">{{ $latestLogin->success ? 'Successful login' : 'Auth failure' }}</span>
                            <small class="ms-auto text-muted">{{ $latestLogin->login_time?->diffForHumans() }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
