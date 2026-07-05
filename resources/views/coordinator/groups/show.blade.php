@extends('layouts.app')

@section('title', $group->name)

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><a href="{{ route('coordinator.index') }}">{{ __('Coordinator workspace') }}</a></li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ \Illuminate\Support\Str::limit($group->name, 40) }}</span></li>
@endsection

@section('content')
    @php
        $memberCount = $members->count();
        $isIndividual = $memberCount === 1;
    @endphp

    <x-prms-greeting-banner
        :title="$group->name"
        :subtitle="$isIndividual ? 'Individual project formation' : 'Project group with '.$memberCount.' members'"
        :showHello="false">
        <x-slot:meta>
            <div class="d-flex flex-wrap gap-2 align-items-center small">
                <span class="badge bg-info rounded-pill">
                    {{ $isIndividual ? 'Individual' : $memberCount.' members' }}
                </span>
                @if ($group->academic_year)
                    <span class="badge bg-light text-dark border rounded-pill">{{ $group->academic_year }}</span>
                @endif
                @if ($group->created_at)
                    <span class="text-muted">Formed {{ $group->created_at->format('d M Y') }}</span>
                @endif
            </div>
        </x-slot:meta>
        <a href="{{ route('coordinator.index') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Workspace
        </a>
    </x-prms-greeting-banner>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h2 class="h6 fw-bold text-primary mb-0 d-flex align-items-center">
                        <i class="fas fa-user-shield text-success me-2" aria-hidden="true"></i>
                        Assigned supervisor
                    </h2>
                </div>
                <div class="card-body">
                    @if ($supervisor)
                        @php
                            $supervisorName = $supervisorStaff?->full_name ?? $supervisor->name;
                            $staffNumber = $supervisorStaff?->staff_number ?? $supervisor->login_id;
                            $supervisorDept = $supervisorStaff?->department?->department_name;
                            $supervisorEmail = $supervisorStaff?->email ?? $supervisor->email;
                            $supervisorPhone = $supervisorStaff?->phone_number;
                        @endphp
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center bg-success-soft text-success rounded-circle fw-bold flex-shrink-0"
                                  style="width: 48px; height: 48px; font-size: 1rem;">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($supervisorName, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-strong">{{ $supervisorName }}</div>
                                @if ($staffNumber)
                                    <code class="small bg-surface-soft px-1 rounded">{{ $staffNumber }}</code>
                                @endif
                            </div>
                        </div>
                        <dl class="row small mb-0 gy-2">
                            @if ($supervisorDept)
                                <dt class="col-sm-4 text-muted">Department</dt>
                                <dd class="col-sm-8 mb-0">{{ $supervisorDept }}</dd>
                            @endif
                            @if ($supervisorStaff?->designation)
                                <dt class="col-sm-4 text-muted">Designation</dt>
                                <dd class="col-sm-8 mb-0">{{ $supervisorStaff->designation }}</dd>
                            @endif
                            @if ($supervisorEmail)
                                <dt class="col-sm-4 text-muted">Email</dt>
                                <dd class="col-sm-8 mb-0 text-break">{{ $supervisorEmail }}</dd>
                            @endif
                            @if ($supervisorPhone)
                                <dt class="col-sm-4 text-muted">Phone</dt>
                                <dd class="col-sm-8 mb-0">{{ $supervisorPhone }}</dd>
                            @endif
                        </dl>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-user-slash opacity-25 mb-2 d-block" style="font-size: 2rem;" aria-hidden="true"></i>
                            <p class="mb-0 small">No supervisor assigned yet.</p>
                            <a href="{{ route('coordinator.index') }}#assign_group" class="btn btn-sm btn-outline-primary rounded-pill mt-3">
                                Assign from workspace
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h2 class="h6 fw-bold text-primary mb-0 d-flex align-items-center">
                        <i class="fas fa-users text-primary me-2" aria-hidden="true"></i>
                        Group members
                    </h2>
                    <span class="badge bg-primary rounded-pill">{{ $memberCount }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-4">Student</th>
                                    <th scope="col">Reg. no</th>
                                    <th scope="col">Programme</th>
                                    <th scope="col" class="text-nowrap">Year</th>
                                    <th scope="col">Department</th>
                                    <th scope="col" class="pe-4">Supervisor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($members as $member)
                                    @php
                                        $profile = $member->studentProfile;
                                        $regNo = $member->regNo() ?? '—';
                                        $programme = $profile?->programme?->programme_code
                                            ?? $profile?->programme?->programme_name
                                            ?? $member->programme
                                            ?? '—';
                                        $year = $profile?->year_of_study ?? $member->year_of_study;
                                        $department = $member->department
                                            ?? $profile?->department?->department_name
                                            ?? data_get($profile?->sis_data, 'department')
                                            ?? '—';
                                        $supervisorLabel = $supervisorStaff?->full_name
                                            ?? $supervisor?->name
                                            ?? 'Unassigned';
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle fw-bold flex-shrink-0"
                                                      style="width: 36px; height: 36px; font-size: 0.85rem;">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->name, 0, 1)) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-strong">{{ $member->name }}</div>
                                                    @if ($member->email)
                                                        <div class="small text-muted text-truncate" style="max-width: 220px;">
                                                            {{ $member->email }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="small bg-surface-soft px-1 rounded">{{ $regNo }}</code>
                                        </td>
                                        <td class="small">{{ $programme }}</td>
                                        <td class="small text-muted">
                                            {{ $year ? 'Year '.$year : '—' }}
                                        </td>
                                        <td class="small text-muted">{{ $department }}</td>
                                        <td class="pe-4 small">
                                            <span class="{{ $supervisor ? 'text-success fw-semibold' : 'text-danger' }}">
                                                {{ $supervisorLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted small">
                                            No members in this group.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
