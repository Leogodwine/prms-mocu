@extends('layouts.app')

@section('title', 'Department overview')

@section('content')
    <x-prms-greeting-banner :subtitle="'Departmental oversight for '.$deptName.' — supervisor workload, student groups, and research progress.'">
        <x-slot:meta>
            <p class="small text-muted mb-0 d-flex align-items-center gap-2 flex-wrap">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-brand-soft text-primary"
                      style="width: 2rem; height: 2rem;">
                    <i class="fas fa-chart-pie" style="font-size: 0.75rem;" aria-hidden="true"></i>
                </span>
                <span><strong class="text-strong">{{ $groupsTotal ?? $recentGroups->total() }}</strong> groups under supervision</span>
            </p>
        </x-slot:meta>
        <a href="{{ route('hod.students.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-user-graduate me-2" aria-hidden="true"></i> Student records
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="prms-stat-card">
                <div class="stat-label">Supervisors</div>
                <div class="stat-value">{{ $supervisors->total() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="prms-stat-card" style="--prms-primary: var(--prms-color-warning-500);">
                <div class="stat-label">Pending reviews</div>
                <div class="stat-value">{{ $submissionStats['pending'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="prms-stat-card" style="--prms-primary: var(--prms-color-success-500);">
                <div class="stat-label">Approved</div>
                <div class="stat-value">{{ $submissionStats['approved'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="prms-stat-card" style="--prms-primary: var(--prms-color-info-500);">
                <div class="stat-label">Total groups</div>
                <div class="stat-value">{{ $groupsTotal ?? $recentGroups->total() }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-hard-hat text-primary me-2" aria-hidden="true"></i>
                        Supervisor workload
                    </h3>
                </div>
                <div class="card-body p-0">
                    <x-prms-table-pagination-toolbar :paginator="$supervisors" noun="supervisors" />
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Supervisor</th>
                                    <th scope="col" class="text-center">Load</th>
                                    <th scope="col" class="text-end">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($supervisors as $staff)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-strong">{{ $staff->full_name }}</div>
                                            <div class="text-muted small">{{ $staff->designation }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $staff->supervisorAssignments->count() > 5 ? 'bg-warning' : 'bg-success' }}">
                                                {{ $staff->supervisorAssignments->count() }} groups
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="progress ms-auto" style="height: 6px; width: 100px;">
                                                <div class="progress-bar" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">No supervisors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-transparent border-top py-3">
                        <x-prms-table-pagination-footer :paginator="$supervisors" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h3 class="h6 fw-bold text-strong mb-0 d-flex align-items-center">
                        <i class="fas fa-history text-success me-2" aria-hidden="true"></i>
                        Recent activity
                    </h3>
                </div>
                <div class="card-body p-0">
                    <x-prms-table-pagination-toolbar :paginator="$recentGroups" noun="groups" />
                    <div>
                        @forelse ($recentGroups as $group)
                            <div class="p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h4 class="h6 fw-bold mb-0 text-strong">{{ $group->name }}</h4>
                                    <span class="badge bg-primary">Stage {{ $group->current_stage_id ?? 1 }}</span>
                                </div>
                                <div class="small text-muted d-flex align-items-center mb-2">
                                    <i class="fas fa-user me-2 text-primary" aria-hidden="true"></i>
                                    Lead supervisor:
                                    <span class="ms-1 fw-semibold text-strong">
                                        {{ optional(optional($group->supervisorAssignment)->supervisor)->name ?? 'Unassigned' }}
                                    </span>
                                </div>
                                @php $percent = (($group->current_stage_id ?? 1) / 7) * 100; @endphp
                                <div class="progress mt-2" style="height: 4px;">
                                    <div class="progress-bar bg-success" style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="far fa-folder-open opacity-25 mb-2 d-block" style="font-size: 2.5rem;" aria-hidden="true"></i>
                                <p class="mb-0 small">No research groups found.</p>
                            </div>
                        @endforelse
                    </div>
                    <x-prms-table-pagination-footer :paginator="$recentGroups" />
                </div>
            </div>
        </div>
    </div>
@endsection
