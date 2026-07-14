@extends('layouts.app')

@section('title', 'System monitoring')

@section('content')
    @php
        $m = $monitor;
    @endphp

    <x-prms-greeting-banner subtitle="Monitor platform performance, server resources, error logs, and maintenance tasks.">
        <a href="{{ route('admin.backups.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-database me-1" aria-hidden="true"></i> Backup and recovery
        </a>
    </x-prms-greeting-banner>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="prms-stat-card text-center py-3" style="--prms-primary: var(--prms-color-success-500);">
                <div class="stat-label">Platform</div>
                <div class="stat-value {{ $m['online'] ? 'text-success' : 'text-warning' }}">
                    {{ $m['online'] ? 'Online' : 'Maintenance' }}
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="prms-stat-card text-center py-3">
                <div class="stat-label">{{ $m['database']['label'] }}</div>
                <div class="stat-value">{{ $m['database']['detail'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="prms-stat-card text-center py-3">
                <div class="stat-label">Memory usage</div>
                <div class="stat-value">{{ $m['memory']['peak_mb'] }} MB</div>
                <div class="small text-muted">Peak · {{ $m['memory']['percent'] ?? '—' }}% of {{ $m['memory']['limit'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="prms-stat-card text-center py-3">
                <div class="stat-label">Disk free</div>
                <div class="stat-value">{{ $m['disk']['free_gb'] }} GB</div>
                <div class="small text-muted">
                    @if ($m['disk']['used_percent'] !== null)
                        {{ $m['disk']['used_percent'] }}% used
                    @else
                        Storage path
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h2 class="h6 fw-bold mb-0">Environment</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($m['environment'] as $row)
                                <tr>
                                    <th scope="row" class="ps-3 text-muted fw-normal">{{ $row['label'] }}</th>
                                    <td class="pe-3 fw-semibold">{{ $row['value'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h2 class="h6 fw-bold mb-0">Maintenance mode</h2>
                </div>
                <div class="card-body">
                    @if ($m['maintenance'])
                        <div class="alert alert-warning">
                            Maintenance mode is <strong>ON</strong>.
                            @if ($m['maintenance_message'])
                                <div class="mt-1">{{ $m['maintenance_message'] }}</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.system-health.maintenance.disable') }}">
                            @csrf
                            <button type="submit" class="btn btn-success rounded-pill px-4">
                                <i class="fas fa-play me-1" aria-hidden="true"></i> Bring application online
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.system-health.maintenance.enable') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="maintenance_message" class="form-label">Optional message for visitors</label>
                                <textarea id="maintenance_message" name="maintenance_message" rows="2"
                                          class="form-control"
                                          placeholder="We are performing scheduled maintenance...">{{ old('maintenance_message') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-warning rounded-pill px-4">
                                <i class="fas fa-pause me-1" aria-hidden="true"></i> Enable maintenance mode
                            </button>
                        </form>
                    @endif

                    <hr class="my-4">

                    <h3 class="h6 fw-bold mb-3">Maintenance tasks</h3>
                    <p class="small text-muted">Run safe commands after updates or configuration changes.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.system-health.maintenance.task') }}">
                            @csrf
                            <input type="hidden" name="task" value="optimize">
                            <button type="submit" class="btn btn-light border btn-sm">Optimize caches</button>
                        </form>
                        <form method="POST" action="{{ route('admin.system-health.maintenance.task') }}">
                            @csrf
                            <input type="hidden" name="task" value="clear">
                            <button type="submit" class="btn btn-light border btn-sm">Clear caches</button>
                        </form>
                        <form method="POST" action="{{ route('admin.system-health.maintenance.task') }}">
                            @csrf
                            <input type="hidden" name="task" value="queue-restart">
                            <button type="submit" class="btn btn-light border btn-sm">Restart queue workers</button>
                        </form>
                        <form method="POST" action="{{ route('admin.system-health.heartbeat') }}">
                            @csrf
                            <button type="submit" class="btn btn-light border btn-sm">Update queue heartbeat</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $storageIssues = collect($m['storage_checks'])->where('ok', false);
    @endphp
    @if ($storageIssues->isNotEmpty())
        <div class="alert alert-danger">
            <strong>Storage permission issue detected.</strong>
            The web server cannot write to:
            @foreach ($storageIssues as $check)
                <code class="d-block small mt-1">{{ $check['path'] }}</code>
            @endforeach
            On Linux run:
            <code class="d-block small mt-2">sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R ug+rwx storage bootstrap/cache</code>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0">Recent error log (last 150 lines)</h2>
        </div>
        <div class="card-body p-0">
            <pre class="mb-0 p-3 small bg-dark text-light" style="max-height: 420px; overflow: auto; white-space: pre-wrap;">@foreach ($m['log_lines'] as $line){{ $line }}
@endforeach</pre>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Failed queue jobs ({{ $queueFailed ?? 0 }})</span>
                    @if (($queueFailed ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.system-health.failed-jobs.clear') }}" class="m-0">
                            @csrf
                            <button class="btn btn-sm btn-link text-danger p-0" type="submit">Clear all</button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <x-prms-table-pagination-toolbar :paginator="$failedJobs" noun="jobs" />
                    @forelse ($failedJobs as $job)
                        <div class="border-bottom p-3 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">Job #{{ $job->id }}</div>
                                <div class="small text-muted">{{ $job->failed_at }} · {{ $job->queue }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.system-health.failed-jobs.retry', $job->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" type="submit">Retry</button>
                            </form>
                        </div>
                    @empty
                        <div class="p-4 text-muted small text-center">No failed jobs.</div>
                    @endforelse
                    <x-prms-table-pagination-footer :paginator="$failedJobs" />
                </div>
                <div class="card-footer small text-muted">Worker heartbeat: {{ $queueHeartbeat ?: 'Unknown' }}</div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h3 class="h6 fw-bold">Security snapshot (24h)</h3>
                    <p class="mb-1 small">Audit events: <strong>{{ $recentAuditCount }}</strong></p>
                    <p class="mb-0 small">Login failures: <strong>{{ $recentLoginFailures }}</strong></p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h3 class="h6 fw-bold">SMS gateway</h3>
                    <p class="mb-2 small">
                        Status:
                        <span class="badge {{ ($smsStatus['ok'] ?? false) ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ ($smsStatus['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                        </span>
                    </p>
                    <p class="mb-1 small text-muted">{{ $smsStatus['detail'] ?? '—' }}</p>
                    <p class="mb-1 small text-muted">
                        Driver: {{ $smsStatus['driver'] ?? '—' }}
                        · Provider: {{ $smsStatus['provider'] ?? 'nextsms' }}
                        · Sender: {{ $smsStatus['sender_id'] ?? '—' }}
                        @if ($smsStatus['test_mode'] ?? false)
                            · <span class="text-warning-emphasis">Test mode</span>
                        @endif
                    </p>
                    @if (! empty($smsStatus['url']))
                        <p class="mb-3 small text-muted text-break">Endpoint: {{ $smsStatus['url'] }}</p>
                    @else
                        <p class="mb-3 small text-muted">Endpoint: —</p>
                    @endif

                    <form method="POST" action="{{ route('admin.system-health.sms-test') }}" class="mb-0">
                        @csrf
                        <label for="sms_test_phone" class="form-label small fw-semibold">Send test SMS</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="sms_test_phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="{{ \App\Support\PrmsSms::E164_EXAMPLE }}" value="{{ old('phone') }}" required>
                            <button type="submit" class="btn btn-outline-primary">Send</button>
                        </div>
                        @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <p class="small text-muted mt-2 mb-0">Uses the configured gateway when SMS is enabled; otherwise logs only.</p>
                    </form>
                </div>
            </div>

            @if (($recentSmsLogs ?? collect())->isNotEmpty())
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-0">
                        <div class="px-3 py-2 border-bottom">
                            <h3 class="h6 fw-bold mb-0">Recent SMS deliveries</h3>
                        </div>
                        @foreach ($recentSmsLogs as $log)
                            <div class="border-bottom px-3 py-2 small">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-semibold">{{ $log->phone }}</span>
                                    <span class="badge {{ $log->status === 'sent' ? 'bg-success' : ($log->status === 'skipped' ? 'bg-secondary' : 'bg-danger') }}">
                                        {{ $log->status }}
                                    </span>
                                </div>
                                <div class="text-muted">{{ Str::limit($log->message, 80) }}</div>
                                @if ($log->status === 'failed' && filled($log->provider_response))
                                    <div class="text-danger small text-break">{{ Str::limit($log->provider_response, 200) }}</div>
                                @endif
                                <div class="text-muted">{{ $log->created_at?->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($latestSisSync)
                <div class="card border-0 shadow-sm">
                    <div class="card-body small">
                        <h3 class="h6 fw-bold">Latest SIS sync</h3>
                        <span class="badge {{ $latestSisSync->sync_status === 'success' ? 'bg-success' : 'bg-danger' }}">
                            {{ strtoupper($latestSisSync->sync_status) }}
                        </span>
                        <span class="text-muted ms-2">{{ $latestSisSync->sync_timestamp?->diffForHumans() }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
