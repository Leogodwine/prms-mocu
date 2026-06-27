@extends('layouts.app')

@section('title', 'Coordinator analytics')

@section('content')
    <x-prms-greeting-banner :subtitle="$reportLead ?? 'Throughput, status mix and stage progress for the project groups under your coordination.'">
        <a href="{{ route('reports.coordinator.export') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-download me-2" aria-hidden="true"></i> Export CSV
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    @php
        $reportViewer = auth()->user();
        $coordinatorReportGroupsUrl = match ($reportViewer->role) {
            'coordinator' => route('coordinator.index'),
            'hod' => route('hod.index'),
            default => route('dashboard'),
        };
        $materialsAll = route('reports.coordinator.materials');
        $materialsPending = route('reports.coordinator.materials', ['apply_status' => 'pending']);
        $materialsApproved = route('reports.coordinator.materials', ['apply_status' => 'approved']);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <a href="{{ $coordinatorReportGroupsUrl }}"
               class="prms-stat-card prms-stat-card--link text-reset text-decoration-none d-block h-100">
                <div class="stat-label">Project groups</div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-value">{{ $totalGroups }}</div>
                    <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <i class="fas fa-users" aria-hidden="true"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ $materialsAll }}"
               class="prms-stat-card prms-stat-card--link text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-info-500);">
                <div class="stat-label">Total materials</div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-value">{{ $totalSubmissions }}</div>
                    <div class="bg-info-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;color:var(--prms-color-info-500);">
                        <i class="far fa-file-alt" aria-hidden="true"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ $materialsApproved }}"
               class="prms-stat-card prms-stat-card--link text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-success-500);">
                <div class="stat-label">Approved work</div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-value">{{ $approvedSubmissions }}</div>
                    <div class="bg-success-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;color:var(--prms-color-success-500);">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ $materialsPending }}"
               class="prms-stat-card prms-stat-card--link text-reset text-decoration-none d-block h-100"
               style="--prms-primary: var(--prms-color-warning-500);">
                <div class="stat-label">Pending review</div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-value">{{ $pendingSubmissions }}</div>
                    <div class="bg-warning-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;color:var(--prms-color-warning-500);">
                        <i class="far fa-clock" aria-hidden="true"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @php
        $hasData = $totalSubmissions > 0;
        $approvalRate = $totalSubmissions > 0 ? round(($approvedSubmissions / $totalSubmissions) * 100, 1) : 0;
        $rejectionRate = $totalSubmissions > 0 ? round(($rejectedSubmissions / $totalSubmissions) * 100, 1) : 0;
        $reviewRate = $totalSubmissions > 0 ? round(($reviewedSubmissions / $totalSubmissions) * 100, 1) : 0;
    @endphp

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-chart-pie text-primary me-2" aria-hidden="true"></i>
                        Status distribution
                    </h3>
                    <span class="prms-eyebrow">{{ $totalSubmissions }} records</span>
                </div>
                <div class="card-body">
                    @if ($hasData && count($statusMix['values']) > 0)
                        <div style="position: relative; height: 260px;">
                            <canvas id="prmsStatusChart" aria-label="Submission status distribution chart" role="img"></canvas>
                        </div>
                        <dl class="row mt-3 mb-0 small">
                            <dt class="col-7 text-muted">Approval rate</dt>
                            <dd class="col-5 text-end fw-semibold text-success">{{ $approvalRate }}%</dd>
                            <dt class="col-7 text-muted">Rejection rate</dt>
                            <dd class="col-5 text-end fw-semibold text-danger">{{ $rejectionRate }}%</dd>
                            <dt class="col-7 text-muted">Reviewed</dt>
                            <dd class="col-5 text-end fw-semibold text-strong">{{ $reviewRate }}%</dd>
                        </dl>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="far fa-folder-open fa-2x opacity-50 mb-2" aria-hidden="true"></i>
                            <p class="mb-0">No submissions recorded yet.</p>
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
                            <p class="mb-0">Stage data will appear once students submit work.</p>
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
                        Monthly throughput (last 6 months)
                    </h3>
                    <span class="prms-eyebrow">Submissions vs. approvals</span>
                </div>
                <div class="card-body">
                    @if (array_sum($monthlyTrend['submitted']) > 0)
                        <div style="position: relative; height: 280px;">
                            <canvas id="prmsTrendChart" aria-label="Monthly submissions and approvals" role="img"></canvas>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-line fa-2x opacity-50 mb-2" aria-hidden="true"></i>
                            <p class="mb-0">Trend data will populate as submissions roll in.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-hard-hat text-primary me-2" aria-hidden="true"></i>
                        Supervisory bandwidth
                    </h3>
                </div>
                <div class="card-body p-4">
                    <p class="display-5 fw-bold text-strong mb-1">{{ $assignedSupervisors }}</p>
                    <p class="text-muted mb-3">Supervisors actively assigned to your groups.</p>
                    @php
                        $coverage = $totalGroups > 0 ? min(100, round(($assignedSupervisors / max($totalGroups, 1)) * 100)) : 0;
                    @endphp
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted">Group coverage</span>
                        <span class="fw-semibold text-strong">{{ $coverage }}%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $coverage }}%;"
                             aria-valuenow="{{ $coverage }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-bullseye text-success me-2" aria-hidden="true"></i>
                        Approval efficiency
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold text-strong">Approved share</span>
                        <span class="fw-bold text-success">{{ $approvalRate }}%</span>
                    </div>
                    <div class="progress" style="height: 18px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $approvalRate }}%;"
                             aria-valuenow="{{ $approvalRate }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $approvalRate }}%
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Share of submissions that reached the approved state.</p>
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
<script>
    (function () {
        const statusData = @json($statusMix);
        const stageData = @json($stageMix);
        const trendData = @json($monthlyTrend);

        function ready(fn) { (typeof Chart === 'undefined') ? setTimeout(() => ready(fn), 50) : fn(); }

        ready(function () {
            const baseFont = "Inter, system-ui, sans-serif";
            Chart.defaults.font.family = baseFont;
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
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } }
                        }
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
                    type: 'line',
                    data: {
                        labels: trendData.labels,
                        datasets: [
                            {
                                label: 'Submitted',
                                data: trendData.submitted,
                                borderColor: '#1f47b8',
                                backgroundColor: 'rgba(31, 71, 184, 0.12)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 4,
                                pointBackgroundColor: '#1f47b8'
                            },
                            {
                                label: 'Approved',
                                data: trendData.approved,
                                borderColor: '#0f9d58',
                                backgroundColor: 'rgba(15, 157, 88, 0.12)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 4,
                                pointBackgroundColor: '#0f9d58'
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
