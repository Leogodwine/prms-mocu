@extends('layouts.app')

@php
    $archivePageTitle = match ($lockedWorkType ?? null) {
        'proposal' => 'Students proposals',
        'research' => 'Students reports',
        'project' => 'Students projects',
        default => 'Approved project and research',
    };
    $archiveRouteParams = ! empty($lockedWorkType) ? ['type' => $lockedWorkType] : [];
@endphp

@section('title', $archivePageTitle)

@section('content')
    @php
        $archiveSubtitle = match ($lockedWorkType ?? null) {
            'proposal' => 'Browse and search approved student proposals by chapter and stage.',
            'research' => 'Browse and search approved student research reports by chapter and stage.',
            'project' => 'Browse and search approved student projects by chapter and stage.',
            default => 'Browse and search approved project and research work by chapter and stage.',
        };
        if ($canTrackProgress ?? false) {
            $archiveSubtitle .= ' Use the status cards and filters below to track progress by stage.';
        }
    @endphp
    <x-prms-greeting-banner :subtitle="$archiveSubtitle">
        <a href="{{ route('archive.export', $filters) }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="fas fa-download me-2" aria-hidden="true"></i> Export CSV
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Dashboard
        </a>
    </x-prms-greeting-banner>

    @if ($canTrackProgress ?? false)
        <div class="row g-3 mb-4">
            @foreach ($workTypes as $type)
                @php
                    $stat = $progress[$type] ?? ['approved' => 0, 'in_progress' => 0, 'label' => ucfirst($type)];
                    $icon = match ($type) {
                        'proposal' => 'fa-file-signature',
                        'research' => 'fa-book-open',
                        'project' => 'fa-laptop-code',
                        default => 'fa-folder',
                    };
                    $tone = match ($type) {
                        'proposal' => 'primary',
                        'research' => 'info',
                        'project' => 'success',
                        default => 'secondary',
                    };
                    $cardCol = count($workTypes) === 1 ? 'col-md-4' : 'col-6 col-md-4 col-lg-3 col-xxl-2';
                    $statusFilter = $filters['status'] ?? 'approved';
                    $typeFilter = $filters['type'] ?? ($lockedWorkType ?? '');
                    $approvedActive = $statusFilter === 'approved' && $typeFilter === $type;
                    $inProgressActive = $statusFilter === 'in_progress' && $typeFilter === $type;
                    $allActive = $statusFilter === 'all' && $typeFilter === $type;
                @endphp
                <div class="{{ $cardCol }}">
                    <form method="POST" action="{{ route('archive.index', $archiveRouteParams) }}" class="card border-0 shadow-sm h-100 card-interactive @if($approvedActive) border border-success border-2 @endif">
                        @csrf
                        <input type="hidden" name="_filter_action" value="apply">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="card-body border-0 bg-transparent text-start w-100 h-100">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-soft text-success"
                                      style="width: 2.5rem; height: 2.5rem;">
                                    <i class="fas {{ $icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="badge bg-{{ $tone }} rounded-pill">{{ $stat['label'] }}</span>
                            </div>
                            <p class="h4 fw-bold text-strong mb-1">{{ $stat['approved'] }}</p>
                            <p class="small text-muted mb-0">Approved</p>
                        </button>
                    </form>
                </div>
                <div class="{{ $cardCol }}">
                    <form method="POST" action="{{ route('archive.index', $archiveRouteParams) }}" class="card border-0 shadow-sm h-100 card-interactive @if($inProgressActive) border border-warning border-2 @endif">
                        @csrf
                        <input type="hidden" name="_filter_action" value="apply">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" class="card-body border-0 bg-transparent text-start w-100 h-100">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-warning-soft text-warning"
                                      style="width: 2.5rem; height: 2.5rem;">
                                    <i class="fas fa-spinner" aria-hidden="true"></i>
                                </span>
                                <span class="badge bg-{{ $tone }} rounded-pill">{{ $stat['label'] }}</span>
                            </div>
                            <p class="h4 fw-bold text-strong mb-1">{{ $stat['in_progress'] }}</p>
                            <p class="small text-muted mb-0">In progress</p>
                        </button>
                    </form>
                </div>
                <div class="{{ $cardCol }}">
                    <form method="POST" action="{{ route('archive.index', $archiveRouteParams) }}" class="card border-0 shadow-sm h-100 card-interactive @if($allActive) border border-primary border-2 @endif">
                        @csrf
                        <input type="hidden" name="_filter_action" value="apply">
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        <input type="hidden" name="status" value="all">
                        <button type="submit" class="card-body border-0 bg-transparent text-start w-100 h-100">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-brand-soft text-primary"
                                      style="width: 2.5rem; height: 2.5rem;">
                                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                                </span>
                                <span class="badge bg-{{ $tone }} rounded-pill">{{ $stat['label'] }}</span>
                            </div>
                            <p class="h4 fw-bold text-strong mb-1">{{ $stat['total'] ?? ($stat['approved'] + $stat['in_progress']) }}</p>
                            <p class="small text-muted mb-0">All statuses</p>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    @if (empty($lockedWorkType))
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach (['' => 'All types', 'proposal' => 'Proposals', 'research' => 'Research', 'project' => 'Projects'] as $value => $label)
            <form method="POST" action="{{ route('archive.index') }}" class="d-inline">
                @csrf
                <input type="hidden" name="_filter_action" value="apply">
                <input type="hidden" name="type" value="{{ $value }}">
                <input type="hidden" name="stage" value="{{ $filters['stage'] ?? '' }}">
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'approved' }}">
                <button type="submit" class="btn btn-sm rounded-pill {{ ($filters['type'] ?? '') === $value ? 'btn-primary' : 'btn-light border' }}">
                    {{ $label }}
                </button>
            </form>
        @endforeach
    </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="POST" action="{{ route('archive.index', $archiveRouteParams) }}">
                @csrf
                <input type="hidden" name="_filter_action" value="apply">
                <input type="hidden" name="type" value="{{ $filters['type'] ?? ($lockedWorkType ?? '') }}">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'approved' }}">
                <div class="row g-2 align-items-end flex-lg-nowrap">
                    <div class="col-md-6 col-lg-7">
                        <label for="archive-q" class="form-label small text-muted mb-1">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent"><i class="fas fa-search text-muted" aria-hidden="true"></i></span>
                            <input id="archive-q" name="q" value="{{ $filters['q'] }}" placeholder="Title, student, group, or stage…" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-5">
                        <label for="archive-stage" class="form-label small text-muted mb-1">Stage</label>
                        <select id="archive-stage" name="stage" class="form-select">
                            <option value="">All stages</option>
                            @foreach ($stages as $stageOption)
                                <option value="{{ $stageOption }}" @selected(($filters['stage'] ?? '') === $stageOption)>{{ $stageOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($canTrackProgress ?? false)
        @php
            $showArchiveReview = auth()->user()?->role === 'supervisor';
            $archiveReviewReturnUrl = route('archive.index', $archiveRouteParams);
            $useProjectCards = ($lockedWorkType ?? null) === 'project';
            $archiveEmptyMessage = match ($lockedWorkType ?? null) {
                'proposal' => 'No matching proposal records. Try another stage or search term.',
                'research' => 'No matching research report records. Try another stage or search term.',
                'project' => 'No matching project records. Try another stage or search term.',
                default => 'No matching records. Try another work type, stage, or search term.',
            };
        @endphp

        @if ($useProjectCards)
            @include('partials.submission-project-cards', [
                'submissions' => $submissions,
                'showReview' => $showArchiveReview,
                'emptyMessage' => $archiveEmptyMessage,
            ])
        @else
            @include('partials.submission-registry-table', [
                'submissions' => $submissions,
                'showReview' => $showArchiveReview,
                'emptyMessage' => $archiveEmptyMessage,
            ])
        @endif

        @if ($showArchiveReview && $submissions->isNotEmpty())
            @foreach ($submissions as $submission)
                @include('partials.submission-review-modal', [
                    'submission' => $submission,
                    'redirectTo' => $archiveReviewReturnUrl,
                ])
            @endforeach
        @endif

        @if ($useProjectCards)
            @include('partials.document-preview-modal')
        @endif
    @else
    <div class="row">
        @forelse ($submissions as $submission)
            @php
                $workType = $submission->work_type;
                $typeBadge = match ($workType) {
                    'proposal' => 'bg-primary',
                    'research' => 'bg-info text-dark',
                    'project' => 'bg-success',
                    default => 'bg-secondary',
                };
                $statusClass = strtolower((string) $submission->status) === 'approved' ? 'success' : 'warning';
            @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm h-100 border-0 feature-card overflow-hidden">
                    <div class="card-header bg-light border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="badge {{ $typeBadge }} rounded-pill">
                                {{ \App\Support\StudentStageProgress::workTypeLabel($workType) }}
                            </span>
                            <span class="badge bg-{{ $statusClass }} rounded-pill flex-shrink-0">
                                {{ ucfirst(str_replace('_', ' ', (string) $submission->status)) }}
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-0 line-clamp-2">{{ $submission->title ?: 'Untitled submission' }}</h5>
                    </div>
                    <div class="card-body pt-3">
                        <p class="text-muted small mb-2">
                            <span class="d-block mb-1">
                                <i class="fas fa-layer-group me-2 text-primary" aria-hidden="true"></i>
                                {{ $submission->stage }}
                            </span>
                            <span class="d-block">
                                <i class="fas fa-user me-2 text-primary" aria-hidden="true"></i>
                                {{ optional($submission->student)->name ?: optional($submission->projectGroup)->name ?: '—' }}
                            </span>
                            @if ($submission->submitted_at)
                                <span class="d-block mt-1">
                                    <i class="far fa-clock me-2 text-primary" aria-hidden="true"></i>
                                    {{ $submission->submitted_at->format('M j, Y') }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                        <small class="text-muted">#{{ $submission->id }}</small>
                        <a href="{{ route('student.submissions.download', $submission) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="fas fa-file-download me-1" aria-hidden="true"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="card shadow-sm p-5">
                    <i class="fas fa-search-minus fa-4x mb-3 text-muted opacity-50" aria-hidden="true"></i>
                    <h4 class="text-muted">No matching records</h4>
                    <p class="text-muted small mb-0">Try another work type, stage, or search term.</p>
                </div>
            </div>
        @endforelse
    </div>

    <x-prms-table-pagination-footer :paginator="$submissions" class="mt-4 px-2" />
    @endif
@endsection

@if (($canTrackProgress ?? false) && auth()->user()?->role === 'supervisor')
@push('scripts')
<script>
    (function () {
        const reviewSubmissionId = @json(old('_submission_id'));
        if (!reviewSubmissionId || !window.bootstrap) {
            return;
        }

        const modalEl = document.getElementById('prmsReviewModal-' + reviewSubmissionId);
        if (modalEl) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    })();
</script>
@endpush
@endif

@push('styles')
<style>
    .feature-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 3rem;
    }
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
