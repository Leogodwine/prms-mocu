@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

@php
    $isStudentWorkspace = $user->isStudentUser();
@endphp

<x-prms-greeting-banner :subtitle="\App\Support\PrmsGreeting::subtitleForRole($user->role ?? null)">
    @if ($isStudentWorkspace && ($canCreateProjects ?? false))
        <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#prmsNewProposalModal">
            <i class="fas fa-plus-circle me-2" aria-hidden="true"></i>
            New project/proposal creation
        </button>
    @elseif ($user->role === 'supervisor')
        <a href="{{ route('supervisor.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-clipboard-check me-2" aria-hidden="true"></i>
            Review submissions
        </a>
    @elseif ($user->role === 'coordinator')
        <a href="{{ route('coordinator.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-users-cog me-2" aria-hidden="true"></i>
            Open hub
        </a>
    @elseif ($user->role === 'hod')
        <a href="{{ route('hod.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-chart-line me-2" aria-hidden="true"></i>
            Department overview
        </a>
    @elseif ($user->role === 'admin')
        <a href="{{ route('admin.users.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-users me-2" aria-hidden="true"></i>
            Manage users
        </a>
    @endif
@if ($isStudentWorkspace && ! empty($availableTracks))
        <x-slot:footer>
            @include('student.partials.academic-calendar', [
                'academicCalendar' => $academicCalendar ?? null,
                'embeddedInGreeting' => true,
            ])
        </x-slot:footer>
    @endif
</x-prms-greeting-banner>

@if ($isStudentWorkspace && ($canCreateProjects ?? false))
    {{-- Initial problem / title submission for supervisor review --}}
    <div class="modal fade" id="prmsNewProposalModal" tabindex="-1" aria-labelledby="prmsNewProposalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <form action="{{ route('projects.problem-proposal.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="prmsNewProposalModalLabel">
                            <i class="fas fa-lightbulb text-primary me-2" aria-hidden="true"></i>
                            New proposal / project
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-4">
                            Describe the <strong>problem you intend to investigate</strong> and a working title.
                            Your supervisor reviews this <em>before</em> you upload formal chapter drafts, so they can confirm or refine the direction with you.
                        </p>

                        @if ($errors->any() && old('_prms_modal') === 'problem-proposal')
                            <div class="alert alert-danger small mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="prms-work-kind">Type</label>
                            <select id="prms-work-kind" name="work_kind" class="form-select @error('work_kind') is-invalid @enderror" required>
                                <option value="proposal" @selected(old('work_kind', 'proposal') === 'proposal')>Research proposal</option>
                                @if (in_array('project', $availableTracks ?? [], true))
                                    <option value="project" @selected(old('work_kind', 'proposal') === 'project')>Computer-based project</option>
                                @endif
                            </select>
                            @error('work_kind')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="prms-proposal-name">Proposal / project short name</label>
                            <input type="text" id="prms-proposal-name" name="proposal_name" maxlength="120"
                                   class="form-control @error('proposal_name') is-invalid @enderror"
                                   value="{{ old('proposal_name') }}"
                                   placeholder="Short label, e.g. MoCU Library QR Module"
                                   required>
                            @error('proposal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">A brief working name your supervisor will see in notifications.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="prms-proposal-title">Full title</label>
                            <input type="text" id="prms-proposal-title" name="title" maxlength="500"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}"
                                   placeholder="Full working title of your study or system"
                                   required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold" for="prms-problem-statement">Problem statement</label>
                            <textarea id="prms-problem-statement" name="problem_statement" rows="6"
                                      class="form-control @error('problem_statement') is-invalid @enderror"
                                      placeholder="What problem are you solving? For whom? Why does it matter? What is your scope?"
                                      required minlength="40" maxlength="8000">{{ old('problem_statement') }}</textarea>
                            @error('problem_statement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">At least 40 characters. Your supervisor uses this to agree on the problem before you proceed with formal submissions.</div>
                        </div>

                        <input type="hidden" name="_prms_modal" value="problem-proposal">
                    </div>
                    <div class="modal-footer flex-wrap gap-2">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>
                            Send to supervisor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- ────────── Coordinator ────────── --}}
@if ($user->role === 'coordinator')
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('coordinator.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fas fa-users-cog" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Groups &amp; assignments</h3>
                    <p class="text-muted small mb-0">Form student groups and balance workload across supervisors.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('reports.coordinator') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info-soft text-info rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px; color: var(--prms-color-info-500);">
                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Reports</h3>
                    <p class="text-muted small mb-0">Stage completion, supervisor workload, and program performance.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('coordinator.submissions') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px; color: var(--prms-color-success-500);">
                            <i class="fas fa-file-signature" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Final submissions</h3>
                    <p class="text-muted small mb-0">Approved work waiting for final coordinator approval.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('coordinator.similarities.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px; color: var(--prms-color-warning-500, #d97706);">
                            <i class="fas fa-clone" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Similar projects &amp; research</h3>
                    <p class="text-muted small mb-0">Background overlap checks between student proposals, reports, and projects.</p>
                </div>
            </a>
        </div>
    </div>
@endif

{{-- ────────── Supervisor ────────── --}}
@if ($user->role === 'supervisor')
    @php
        $assignedSummary = $supervisorAssignments ?? null;
        $assignedLabel = $assignedSummary
            ? \App\Support\SupervisorAssignmentScope::summaryLabel($assignedSummary)
            : 'Groups, individuals, or both';
    @endphp
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('supervisor.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Review submissions</h3>
                    <p class="text-muted small mb-0">Read submissions, leave feedback, and decide on next steps.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('supervisor.workload') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px; color: var(--prms-color-warning-500, #d97706);">
                            <i class="fas fa-user-graduate" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Assigned students</h3>
                    <p class="text-muted small mb-0">View your assigned groups, individual students, or both. <strong class="text-strong">{{ $assignedLabel }}</strong></p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('supervisor.logs') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px; color: var(--prms-color-info-500);">
                            <i class="fas fa-comments" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Research supervision</h3>
                    <p class="text-muted small mb-0">Track supervision meetings, progress assessments, and follow-up activities.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('reports.supervisor') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-soft rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px; color: var(--prms-color-success-500);">
                            <i class="fas fa-chart-bar" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Reports</h3>
                    <p class="text-muted small mb-0">Workload, decisions, and turnaround analytics for your students.</p>
                </div>
            </a>
        </div>
    </div>
@endif

{{-- ────────── Student progress ────────── --}}
@if ($isStudentWorkspace)
    @if (! empty($workflowBlockReason) && empty($availableTracks))
        <div class="alert alert-info border-0 shadow-sm mb-4" role="status">
            <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
            {{ $workflowBlockReason }}
        </div>
    @endif

    <div class="row g-4 mb-4">
    @if (! empty($availableTracks))
        <div class="col-12 col-lg-8">
            <h3 class="h5 fw-bold text-strong mb-3">Your progress</h3>

            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    @include('partials.progress-card', [
                        'label'      => 'Proposal',
                        'percent'    => $proposalProgress,
                        'approved'   => $proposalApproved,
                        'inProgress' => $proposalInProgress ?? 0,
                        'total'      => $proposalTotal ?? 3,
                        'icon'       => 'far fa-file-alt',
                        'tone'       => 'primary',
                        'href'       => route('student.index', ['type' => 'proposal']),
                    ])
                </div>

                @if (in_array('project', $availableTracks ?? [], true))
                    <div class="col-md-6 col-xl-4">
                        @include('partials.progress-card', [
                            'label'      => 'Project',
                            'percent'    => $projectProgress,
                            'approved'   => $projectApproved,
                            'inProgress' => $projectInProgress ?? 0,
                            'total'      => $projectTotal ?? 1,
                            'icon'       => 'fas fa-laptop-code',
                            'tone'       => 'info',
                            'href'       => route('student.index', ['type' => 'project']),
                        ])
                    </div>
                @endif

                @if (in_array('research', $availableTracks ?? [], true))
                    <div class="col-md-6 col-xl-4">
                        @include('partials.progress-card', [
                            'label'      => 'Research / thesis',
                            'percent'    => $researchProgress,
                            'approved'   => $researchApproved,
                            'inProgress' => $researchInProgress ?? 0,
                            'total'      => $researchTotal ?? 5,
                            'icon'       => 'fas fa-book-open',
                            'tone'       => 'success',
                            'href'       => route('student.index', ['type' => 'research']),
                        ])
                    </div>
                @endif
            </div>

            <div class="card mt-4">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-tasks text-primary me-2" aria-hidden="true"></i>
                    <h3 class="card-title h6 fw-bold mb-0">Open your workspace</h3>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <a href="{{ route('student.index') }}" class="btn btn-primary w-100">
                                <i class="fas fa-book-reader me-2" aria-hidden="true"></i> Project &amp; research (all progress)
                            </a>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <a href="{{ route('student.index', ['type' => 'proposal']) }}" class="btn btn-outline-primary w-100">
                                <i class="far fa-file-alt me-2" aria-hidden="true"></i> Proposal
                            </a>
                        </div>
                        @if (in_array('project', $availableTracks ?? [], true))
                            <div class="col-md-4">
                                <a href="{{ route('student.index', ['type' => 'project']) }}" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-laptop-code me-2" aria-hidden="true"></i> Project
                                </a>
                            </div>
                        @endif
                        @if (in_array('research', $availableTracks ?? [], true))
                            <div class="col-md-4">
                                <a href="{{ route('student.index', ['type' => 'research']) }}" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-book-open me-2" aria-hidden="true"></i> Research / thesis
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

        <div class="col-12 {{ ! empty($availableTracks) ? 'col-lg-4' : '' }}">
            @if (! empty($availableTracks))
                <div class="card mb-3">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h3 class="h6 fw-bold mb-0">Workspace guide</h3>
                    </div>
                    <div class="card-body py-3">
                        <ul class="small mb-0 ps-3" style="list-style-type: disc;">
                            <li class="py-2 border-bottom border-soft">
                                <span class="fw-semibold text-strong d-block">Proposal</span>
                                <span class="text-muted d-block mt-1">Chapter drafts before research begins</span>
                            </li>
                            @if (in_array('project', $availableTracks, true))
                                <li class="py-2 border-bottom border-soft">
                                    <span class="fw-semibold text-strong d-block">Project</span>
                                    <span class="text-muted d-block mt-1">Build a working system that solves a real-world problem and submit your source code</span>
                                </li>
                            @endif
                            @if (in_array('research', $availableTracks, true))
                                <li class="py-2">
                                    <span class="fw-semibold text-strong d-block">Research report</span>
                                    <span class="text-muted d-block mt-1">Your thesis, dissertation, or final report, chapter by chapter</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif

            <h3 class="h5 fw-bold text-strong mb-3">Mentorship</h3>

            <div class="card">
                <div class="card-body text-center p-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle mb-3 fw-bold"
                         style="width: 64px; height: 64px; font-size: 1.4rem;">
                        {{ $supervisorAssignment?->supervisor
                            ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($supervisorAssignment->supervisor->name, 0, 1))
                            : '?' }}
                    </div>
                    <h4 class="h6 fw-bold mb-1">{{ $supervisorAssignment?->supervisor->name ?? 'Pending assignment' }}</h4>
                    <span class="prms-eyebrow">Supervisor</span>

                    @if ($supervisorAssignment?->supervisor)
                        <div class="mt-3 small text-muted text-break">
                            <i class="far fa-envelope me-1" aria-hidden="true"></i>
                            {{ $supervisorAssignment->supervisor->email }}
                        </div>
                    @else
                        <p class="text-muted small mt-3 mb-0">
                            Your coordinator will assign a supervisor soon. Please check back shortly.
                        </p>
                    @endif
                </div>
            </div>

            @php
                $groupMembers = $projectGroup?->members ?? collect();
                $isGroupWork = $groupMembers->count() > 1;
            @endphp
            @if ($isGroupWork)
                <div class="card mt-3">
                    <div class="card-body p-4">
                        <div class="text-center mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center bg-info-soft rounded-circle mb-2"
                                 style="width: 48px; height: 48px; color: var(--prms-color-info-500);">
                                <i class="fas fa-users" aria-hidden="true"></i>
                            </div>
                            <h4 class="h6 fw-bold mb-1">{{ $projectGroup->name }}</h4>
                            <span class="prms-eyebrow">Group members</span>
                        </div>
                        <ul class="list-unstyled mb-0">
                            @foreach ($groupMembers->sortBy('name') as $member)
                                <li class="d-flex align-items-center gap-2 py-2{{ $loop->last ? '' : ' border-bottom' }}">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-brand-soft text-primary rounded-circle fw-bold flex-shrink-0"
                                         style="width: 36px; height: 36px; font-size: 0.85rem;">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 text-start">
                                        <div class="fw-semibold small">
                                            {{ $member->name }}
                                            @if ($member->id === $user->id)
                                                <span class="badge rounded-pill bg-brand-soft text-primary ms-1">You</span>
                                            @endif
                                        </div>
                                        @if ($member->email)
                                            <div class="text-muted small text-truncate">
                                                <i class="far fa-envelope me-1" aria-hidden="true"></i>
                                                {{ $member->email }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <a href="{{ route('archive.index') }}" class="card card-interactive text-decoration-none mt-3">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-info-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; color: var(--prms-color-info-500);">
                        <i class="fas fa-archive" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-primary">Approved project and research</div>
                        <small class="text-muted">Browse approved work by type: proposal, research, or project</small>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endif

{{-- ────────── HOD ────────── --}}
@if ($user->role === 'hod')
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <a href="{{ route('hod.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fas fa-university" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Department overview</h3>
                    <p class="text-muted small mb-0">Supervisors, students, groups, and pairings in one view.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('archive.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                             style="width:44px;height:44px; color: var(--prms-color-info-500);">
                            <i class="fas fa-archive" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h5 fw-bold text-primary">Approved project and research</h3>
                    <p class="text-muted small mb-0">Search and filter approved submissions by type and stage.</p>
                </div>
            </a>
        </div>
    </div>
@endif

{{-- ────────── Admin ────────── --}}
@if ($user->role === 'admin')
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <a href="{{ route('admin.users.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fas fa-users" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h6 fw-bold text-primary">Manage users</h3>
                    <p class="text-muted small mb-0">Create, deactivate, and bulk-import accounts.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.configuration.index') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                             style="width:44px;height:44px; color: var(--prms-color-info-500);">
                            <i class="fas fa-cog" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h6 fw-bold text-primary">Configuration</h3>
                    <p class="text-muted small mb-0">System settings, file limits, and policies.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.audit') }}" class="card card-interactive text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success-soft rounded-3 d-inline-flex align-items-center justify-content-center"
                             style="width:44px;height:44px; color: var(--prms-color-success-500);">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h3 class="h6 fw-bold text-primary">Audit log</h3>
                    <p class="text-muted small mb-0">Trace user activity and security events.</p>
                </div>
            </a>
        </div>
    </div>
@endif

@endsection

@push('scripts')
@if ($isStudentWorkspace && $errors->any() && old('_prms_modal') === 'problem-proposal')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('prmsNewProposalModal');
            if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
        });
    </script>
@endif
@endpush
