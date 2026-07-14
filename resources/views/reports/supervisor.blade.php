@extends('layouts.app')

@section('title', 'Supervisor analytics')

@section('content')
    <x-prms-greeting-banner subtitle="Mentorship impact, review throughput, and decision patterns across your assigned groups and individually supervised students.">
        <a href="{{ route('reports.supervisor.export') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-download me-2" aria-hidden="true"></i> Export CSV
        </a>
        <a href="{{ route('supervisor.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Review submissions
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-th-large me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('supervisor.workload') }}"
               class="prms-stat-card prms-stat-card--link text-center py-3 text-reset text-decoration-none d-block h-100">
                <div class="stat-label">Groups</div>
                <div class="stat-value">{{ $totalAssignedGroups }}</div>
                <i class="fas fa-users text-primary opacity-50 mt-2" aria-hidden="true"></i>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('supervisor.index', ['apply_queue' => 'reviewed']) }}"
               class="prms-stat-card prms-stat-card--link text-center py-3 text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-info-500);">
                <div class="stat-label">Reviewed</div>
                <div class="stat-value">{{ $totalReviewed }}</div>
                <i class="fas fa-eye text-info opacity-50 mt-2" aria-hidden="true"></i>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('supervisor.index', ['apply_queue' => 'pending']) }}"
               class="prms-stat-card prms-stat-card--link text-center py-3 text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-warning-500);">
                <div class="stat-label">Action required</div>
                <div class="stat-value {{ $totalPending > 0 ? 'text-danger' : '' }}">{{ $totalPending }}</div>
                <i class="far fa-hourglass text-warning opacity-50 mt-2" aria-hidden="true"></i>
            </a>
        </div>
        <div class="col-6 col-md-6 col-lg-2">
            <a href="{{ route('supervisor.index', ['apply_queue' => 'approved']) }}"
               class="prms-stat-card prms-stat-card--link text-center py-3 text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-success-500);">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $totalApproved }}</div>
                <i class="fas fa-check-circle text-success opacity-50 mt-2" aria-hidden="true"></i>
            </a>
        </div>
        <div class="col-6 col-md-6 col-lg-2">
            <a href="{{ route('supervisor.index', ['apply_queue' => 'rejected']) }}"
               class="prms-stat-card prms-stat-card--link text-center py-3 text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-danger-500);">
                <div class="stat-label">Rejected</div>
                <div class="stat-value">{{ $totalRejected }}</div>
                <i class="fas fa-times-circle text-danger opacity-50 mt-2" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    @php
        $totalDecisions = $totalApproved + $totalRejected;
        $appRate = $totalDecisions > 0 ? round(($totalApproved / $totalDecisions) * 100, 1) : 0;
        $rejRate = $totalDecisions > 0 ? round(($totalRejected / $totalDecisions) * 100, 1) : 0;
        $hasTrend = array_sum($monthlyTrend['submitted']) > 0;
    @endphp

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-chart-pie text-primary me-2" aria-hidden="true"></i>
                        Decision distribution
                    </h3>
                    <span class="prms-eyebrow">{{ $totalSubmissions }} records</span>
                </div>
                <div class="card-body">
                    @if (count($statusMix['values']) > 0)
                        <div style="position: relative; height: 240px;">
                            <canvas id="prmsStatusChart" aria-label="Status distribution chart" role="img"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold text-strong">Approved vs. rejected</span>
                                <span class="text-muted">{{ $totalDecisions }} decisions</span>
                            </div>
                            <div class="progress" style="height: 22px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $appRate }}%;"
                                     aria-valuenow="{{ $appRate }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $appRate ? $appRate.'%' : '' }}
                                </div>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $rejRate }}%;"
                                     aria-valuenow="{{ $rejRate }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $rejRate ? $rejRate.'%' : '' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="far fa-folder-open fa-2x opacity-50 mb-2" aria-hidden="true"></i>
                            <p class="mb-0">No submissions to analyse yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-layer-group text-primary me-2" aria-hidden="true"></i>
                        Submissions by stage
                    </h3>
                    <span class="prms-eyebrow">Top {{ count($stageMix['labels']) }}</span>
                </div>
                <div class="card-body">
                    @if (count($stageMix['values']) > 0)
                        <div style="position: relative; height: 260px;">
                            <canvas id="prmsStageChart" aria-label="Submissions per stage chart" role="img"></canvas>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-bar fa-2x opacity-50 mb-2" aria-hidden="true"></i>
                            <p class="mb-0">Stage data will appear once submissions reach you.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-chart-line text-success me-2" aria-hidden="true"></i>
                        Review throughput (last 6 months)
                    </h3>
                    <span class="prms-eyebrow">Submitted vs. approved</span>
                </div>
                <div class="card-body">
                    @if ($hasTrend)
                        <div style="position: relative; height: 280px;">
                            <canvas id="prmsTrendChart" aria-label="Monthly review throughput" role="img"></canvas>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-line fa-2x opacity-50 mb-2" aria-hidden="true"></i>
                            <p class="mb-0">Throughput data will populate as decisions accumulate.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-inbox text-secondary me-2" aria-hidden="true"></i>
                        Current workload
                    </h3>
                </div>
                <div class="card-body p-4 d-flex flex-column gap-2">
                    <p class="display-5 fw-bold text-strong mb-0">{{ $totalPending }}</p>
                    <p class="text-muted mb-3">
                        {{ $totalPending === 0 ? 'Workload balanced — no documents waiting.' : 'document(s) awaiting your review.' }}
                    </p>
                    <a href="{{ route('supervisor.index') }}" class="btn btn-primary rounded-pill align-self-start px-4">
                        Open queue <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-bullseye text-success me-2" aria-hidden="true"></i>
                        Decision balance
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold text-strong">Approval rate</span>
                        <span class="fw-bold text-success">{{ $appRate }}%</span>
                    </div>
                    <div class="progress" style="height: 18px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $appRate }}%;"
                             aria-valuenow="{{ $appRate }}" aria-valuemin="0" aria-valuemax="100">{{ $appRate }}%</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                        <span class="fw-semibold text-strong">Rejection rate</span>
                        <span class="fw-bold text-danger">{{ $rejRate }}%</span>
                    </div>
                    <div class="progress" style="height: 18px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $rejRate }}%;"
                             aria-valuenow="{{ $rejRate }}" aria-valuemin="0" aria-valuemax="100">{{ $rejRate }}%</div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Distribution across all decided submissions.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    a.prms-stat-card--link {
        transition: box-shadow 0.15s ease, transform 0.15s ease;
        cursor: pointer;
    }
    a.prms-stat-card--link:hover {
        box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.1);
        transform: translateY(-2px);
    }
    a.prms-stat-card--link:focus-visible {
        outline: 2px solid var(--prms-primary, var(--prms-color-primary-500));
        outline-offset: 2px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
    (function () {
        const statusData = @json($statusMix);
        const stageData = @json($stageMix);
        const trendData = @json($monthlyTrend);

        function ready(fn) { (typeof Chart === 'undefined') ? setTimeout(() => ready(fn), 50) : fn(); }

        ready(function () {
            Chart.defaults.font.family = "Inter, system-ui, sans-serif";
            Chart.defaults.color = "#4f5564";

            const statusCanvas = document.getElementById('prmsStatusChart');
            if (statusCanvas && statusData.values.length) {
                new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: statusData.labels,
                        datasets: [{
                            data: statusData.values,
                            backgroundColor: statusData.colours,
                            borderColor: '#ffffff',
                            borderWidth: 2,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } }
                    }
                });
            }

            const stageCanvas = document.getElementById('prmsStageChart');
            if (stageCanvas && stageData.values.length) {
                new Chart(stageCanvas, {
                    type: 'bar',
                    data: {
                        labels: stageData.labels,
                        datasets: [{
                            label: 'Submissions',
                            data: stageData.values,
                            backgroundColor: 'rgba(31, 71, 184, 0.85)',
                            borderRadius: 6,
                            maxBarThickness: 22
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0 } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }

            const trendCanvas = document.getElementById('prmsTrendChart');
            if (trendCanvas && trendData.labels.length) {
                new Chart(trendCanvas, {
                    type: 'bar',
                    data: {
                        labels: trendData.labels,
                        datasets: [
                            {
                                label: 'Submitted',
                                data: trendData.submitted,
                                backgroundColor: 'rgba(31, 71, 184, 0.78)',
                                borderRadius: 5,
                                maxBarThickness: 28
                            },
                            {
                                label: 'Approved',
                                data: trendData.approved,
                                backgroundColor: 'rgba(15, 157, 88, 0.78)',
                                borderRadius: 5,
                                maxBarThickness: 28
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0 } }
                        }
                    }
                });
            }
        });
    })();
</script>
@endpush
