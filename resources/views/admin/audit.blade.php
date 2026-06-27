@extends('layouts.app')

@section('title', 'Audit & security')

@section('content')
    <x-prms-greeting-banner subtitle="System activity and authentication events.">
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-history text-primary me-2" aria-hidden="true"></i>
                        Activity trail
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 550px; overflow-y: auto;">
                        @forelse ($auditLogs as $log)
                            <div class="list-group-item p-3 border-bottom border-start border-3 {{ str_contains(strtolower($log->action), 'delete') ? 'border-danger' : (str_contains(strtolower($log->action), 'create') ? 'border-success' : 'border-info') }}">
                                <div class="d-flex justify-content-between">
                                    <h4 class="h6 fw-bold mb-1 text-strong">{{ $log->action }}</h4>
                                    <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="text-muted d-flex flex-wrap gap-2 mt-1 small">
                                    <span>
                                        <i class="fas fa-user me-1" aria-hidden="true"></i>
                                        {{ $log->user?->name ?? ($log->user_id ? 'User #'.$log->user_id : 'System') }}
                                        @if ($log->user?->email)
                                            <span class="text-muted">({{ $log->user->email }})</span>
                                        @endif
                                    </span>
                                    @if ($log->entity_type)
                                        <span><i class="fas fa-tag me-1" aria-hidden="true"></i> {{ $log->entity_type }}@if($log->entity_id) #{{ $log->entity_id }}@endif</span>
                                    @endif
                                    @if ($log->ip_address)
                                        <span><i class="fas fa-network-wired me-1" aria-hidden="true"></i> {{ $log->ip_address }}</span>
                                    @endif
                                </div>
                                @if (!empty($log->new_values))
                                    <details class="mt-2 small">
                                        <summary class="text-muted">Details</summary>
                                        <pre class="mb-0 mt-1 p-2 bg-light rounded small" style="white-space: pre-wrap;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="far fa-file-alt fs-1 mb-2 d-block opacity-50" aria-hidden="true"></i>
                                <p class="mb-0 small">No logs in this timeframe.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-3">
                    {{ $auditLogs->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-sign-in-alt text-strong me-2" aria-hidden="true"></i>
                        Authentication stream
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 550px; overflow-y: auto;">
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
                    {{ $loginHistory->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
