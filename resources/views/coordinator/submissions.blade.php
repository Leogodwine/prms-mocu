@extends('layouts.app')

@section('title', 'Final Submissions')

@section('content')
    @php
        $defaultFilters = ['type' => '', 'stage' => '', 'status' => 'all', 'q' => ''];
        $hasActiveFilters = collect($filters ?? [])->diffAssoc(collect($defaultFilters))->isNotEmpty();
        $submissionSummary = $submissions->count().' '.($submissions->count() === 1 ? 'submission' : 'submissions')
            .($hasActiveFilters ? ' (filtered)' : '');
    @endphp

    <x-prms-greeting-banner subtitle="Review and finalize complete proposal, research report, and project documents that students have submitted after supervisor approval.">
        <x-slot:meta>
            <p class="small text-strong mb-0">{{ $submissionSummary }}</p>
            @if ($hasActiveFilters)
                <p class="small mb-0 mt-1">
                    <a href="{{ $filterResetUrl }}" class="fw-semibold">Clear all filters</a>
                </p>
            @endif
        </x-slot:meta>
        <a href="{{ route('coordinator.index') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-users-cog me-2" aria-hidden="true"></i> Workspace
        </a>
    </x-prms-greeting-banner>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="h6 fw-bold text-primary d-flex align-items-center mb-0">
            <i class="fas fa-file-circle-check text-primary me-2" aria-hidden="true"></i>
            Final submissions
        </h2>
        @if ($hasActiveFilters)
            <a href="{{ $filterResetUrl }}" class="btn btn-sm btn-light border rounded-pill px-3">
                <i class="fas fa-times me-1" aria-hidden="true"></i> Clear filters
            </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="POST" action="{{ route('coordinator.submissions') }}">
                @csrf
                <input type="hidden" name="_filter_action" value="apply">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label for="coord-final-q" class="form-label small text-muted mb-1">Search</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent"><i class="fas fa-search text-muted" aria-hidden="true"></i></span>
                            <input id="coord-final-q" name="q" value="{{ $filters['q'] ?? '' }}"
                                   placeholder="Title, student, group, or stage…" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label for="coord-final-type" class="form-label small text-muted mb-1">Category</label>
                        <select id="coord-final-type" name="type" class="form-select form-select-sm">
                            <option value="">All categories</option>
                            <option value="proposal" @selected(($filters['type'] ?? '') === 'proposal')>Proposal</option>
                            <option value="research" @selected(($filters['type'] ?? '') === 'research')>Research</option>
                            <option value="project" @selected(($filters['type'] ?? '') === 'project')>Project</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="coord-final-stage" class="form-label small text-muted mb-1">Stage</label>
                        <select id="coord-final-stage" name="stage" class="form-select form-select-sm">
                            <option value="">All stages</option>
                            @foreach ($stages as $stageOption)
                                <option value="{{ $stageOption }}" @selected(($filters['stage'] ?? '') === $stageOption)>{{ $stageOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="coord-final-status" class="form-label small text-muted mb-1">Status</label>
                        <select id="coord-final-status" name="status" class="form-select form-select-sm">
                            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All statuses</option>
                            <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending finalization</option>
                            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Finalized</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach (['' => 'All', 'proposal' => 'Proposal', 'research' => 'Research', 'project' => 'Project'] as $typeValue => $typeLabel)
            <form method="POST" action="{{ route('coordinator.submissions') }}" class="d-inline">
                @csrf
                <input type="hidden" name="_filter_action" value="apply">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">
                <input type="hidden" name="type" value="{{ $typeValue }}">
                <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                <button type="submit" class="btn btn-sm rounded-pill {{ ($filters['type'] ?? '') === $typeValue ? 'btn-primary' : 'btn-light border' }}">
                    {{ $typeLabel }}
                </button>
            </form>
        @endforeach
    </div>

    @php
        $emptyMessage = $hasActiveFilters
            ? 'No submissions match your filters. Try another search term or clear the filters.'
            : 'No complete proposal, research report, or project documents yet. They appear here after supervisor approval and student final submission.';
    @endphp

    @include('partials.submission-registry-table', [
        'submissions' => $submissions,
        'showFinalize' => true,
        'showConsentReview' => true,
        'consentSubmissions' => $consentSubmissions ?? [],
        'useCoordinatorFinalStatus' => true,
        'emptyMessage' => $emptyMessage,
    ])

    @php
        $uniqueConsents = collect($consentSubmissions ?? [])->unique('id');
        $relatedStageByConsent = collect($consentSubmissions ?? [])
            ->mapWithKeys(fn ($consent, $submissionId) => [
                $consent->id => $submissions->firstWhere('id', $submissionId)?->stage,
            ]);
    @endphp
    @foreach ($uniqueConsents as $consent)
        @push('modals')
            @include('coordinator.partials.consent-review-modal', [
                'consent' => $consent,
                'relatedStage' => $relatedStageByConsent[$consent->id] ?? '',
            ])
        @endpush
    @endforeach
@endsection

@push('styles')
<style>
    .submission-registry-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--prms-muted, #475569);
        white-space: nowrap;
    }
    .submission-registry-table tbody tr:nth-child(odd) {
        background-color: rgba(var(--bs-primary-rgb, 29, 78, 216), 0.04);
    }
</style>
@endpush
