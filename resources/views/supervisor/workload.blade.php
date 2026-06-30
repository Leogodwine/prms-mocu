@extends('layouts.app')

@section('title', 'Assigned students')

@section('content')
    @php
        $individualAll = $individualStudentsAll ?? $individualStudents;
        $summary = $assignmentSummary ?? [
            'group_count' => $groups->count(),
            'individual_count' => $individualAll->count(),
            'total_count' => $groups->count() + $individualAll->count(),
        ];
        $summaryLabel = \App\Support\SupervisorAssignmentScope::summaryLabel($summary);
        $groupFilter = $groupFilter ?? 'all';
        $visibleGroups = $visibleGroups ?? $groups;
        $totalGroupMembers = $groups->sum(fn ($group) => $group->members->count());
    @endphp

    <x-prms-greeting-banner subtitle="Full roster for your assigned groups and individual students — registration, gender, programme, and contact details.">
        <x-slot:meta>
            <p class="small text-muted mb-0">
                <strong class="text-strong">{{ $summary['total_count'] }}</strong> assignment{{ $summary['total_count'] === 1 ? '' : 's' }}
                <span class="text-muted">({{ $summaryLabel }})</span>
                @if ($groups->isNotEmpty())
                    · <strong class="text-strong">{{ $totalGroupMembers }}</strong> group member{{ $totalGroupMembers === 1 ? '' : 's' }}
                @endif
            </p>
        </x-slot:meta>
        <a href="{{ route('supervisor.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-clipboard-check me-2" aria-hidden="true"></i> Review submissions
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    @if ($groups->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted fw-semibold me-1">
                        <i class="fas fa-filter me-1" aria-hidden="true"></i> Groups:
                    </span>
                    <a href="{{ route('supervisor.workload', ['group' => 'all']) }}"
                       class="btn btn-sm rounded-pill {{ $groupFilter === 'all' ? 'btn-primary' : 'btn-light border' }}">
                        All groups
                        <span class="ms-1 opacity-75">({{ $groups->count() }})</span>
                    </a>
                    @foreach ($groups as $group)
                        <a href="{{ route('supervisor.workload', ['group' => $group->id]) }}"
                           class="btn btn-sm rounded-pill {{ (string) $groupFilter === (string) $group->id ? 'btn-primary' : 'btn-light border' }}">
                            {{ $group->name }}
                            <span class="ms-1 opacity-75">({{ $group->members_count }})</span>
                        </a>
                    @endforeach
                </div>
                <p class="small text-muted mb-0 mt-2">
                    @if ($groupFilter === 'all')
                        Showing every assigned group and member roster below.
                    @else
                        Showing one group. Switch to <a href="{{ route('supervisor.workload', ['group' => 'all']) }}">all groups</a> to compare rosters side by side.
                    @endif
                </p>
            </div>
        </div>
    @endif

    @forelse ($visibleGroups as $group)
        @php
            $members = $group->members->sortBy('name');
            $maleCount = $members->filter(fn ($member) => $member->studentProfile?->normalizedGender() === 'male')->count();
            $femaleCount = $members->filter(fn ($member) => $member->studentProfile?->normalizedGender() === 'female')->count();
        @endphp
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="h6 fw-bold text-strong mb-1 d-flex align-items-center gap-2">
                            <i class="fas fa-users text-primary" aria-hidden="true"></i>
                            {{ $group->name }}
                        </h2>
                        <div class="small text-muted">
                            {{ $group->members_count }} member{{ $group->members_count === 1 ? '' : 's' }}
                            · <span class="badge bg-info text-dark rounded-pill">Group</span>
                        </div>
                        @if ($group->members_count > 0)
                            <div class="small text-muted mt-1">
                                @if ($maleCount + $femaleCount > 0)
                                    {{ $maleCount }} male · {{ $femaleCount }} female
                                @endif
                                @if ($group->academic_year)
                                    · {{ $group->academic_year }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($groupFilter === 'all')
                            <a href="{{ route('supervisor.workload', ['group' => $group->id]) }}"
                               class="btn btn-sm btn-light border rounded-pill">
                                Focus this group
                            </a>
                        @endif
                        <a href="{{ route('supervisor.logs.create', ['group' => $group->id]) }}"
                           class="btn btn-sm btn-outline-primary rounded-pill">
                            Group meeting history
                        </a>
                        <a href="{{ route('presentation-consent.show', ['group' => $group->id]) }}"
                           class="btn btn-sm btn-outline-secondary rounded-pill"
                           target="_blank" rel="noopener noreferrer">
                            Preview consent
                        </a>
                        <a href="{{ route('presentation-consent.download', ['group' => $group->id]) }}"
                           class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="fas fa-download me-1" aria-hidden="true"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @include('supervisor.partials.assigned-student-roster-table', ['students' => $group->members])
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-users fa-2x opacity-25 mb-2" aria-hidden="true"></i>
                <p class="mb-0 mt-2">No multi-student groups assigned yet.</p>
            </div>
        </div>
    @endforelse

    @if ($individualAll->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="h6 fw-bold text-strong mb-1">Individual students</h2>
                    <div class="small text-muted">{{ $individualAll->count() }} individually supervised student{{ $individualAll->count() === 1 ? '' : 's' }}</div>
                </div>
                <span class="badge bg-secondary rounded-pill">{{ $individualAll->count() }}</span>
            </div>
            <div class="card-body p-0">
                <x-prms-table-pagination-toolbar :paginator="$individualStudents" noun="students" />
                @include('supervisor.partials.assigned-student-roster-table', ['students' => $individualStudents])
                <x-prms-table-pagination-footer :paginator="$individualStudents" />
            </div>
        </div>
    @endif
@endsection
