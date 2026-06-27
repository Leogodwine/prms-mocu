@php
    $group = $submission->projectGroup;
    $members = $group?->members ?? collect();
    $memberCount = $members->count();
    $isGroup = $memberCount > 1;
    $authors = $isGroup
        ? $members->pluck('name')->map(fn ($name) => \Illuminate\Support\Str::title($name))->join(', ')
        : \Illuminate\Support\Str::title(
            optional($submission->student)->name ?? ($members->first()?->name ?? '—')
        );
    $submissionType = $isGroup ? 'Group' : 'Individual';
    $groupNo = $isGroup ? ($group->name ?? '—') : '—';

    $statusBadge = match ($submission->status) {
        'approved'       => 'bg-success',
        'rejected'       => 'bg-danger',
        'needs_revision' => 'bg-warning text-dark',
        'pending', 'submitted', 'under_review' => 'bg-warning text-dark',
        default          => 'bg-secondary',
    };
    $statusIcon = match ($submission->status) {
        'approved'       => 'fas fa-check-circle',
        'rejected'       => 'fas fa-times-circle',
        'needs_revision' => 'fas fa-undo',
        'pending', 'submitted', 'under_review' => 'far fa-clock',
        default          => 'far fa-question-circle',
    };
    $statusLabel = match ($submission->status) {
        'approved'       => 'Approved',
        'rejected'       => 'Rejected',
        'needs_revision' => 'Returned for revision',
        'pending'        => 'Awaiting review',
        'submitted'      => 'Submitted',
        'under_review'   => 'Under review',
        default          => \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->status)),
    };

    $isPdf = str_contains((string) $submission->mime_type, 'pdf')
        || str_ends_with(strtolower((string) $submission->original_filename), '.pdf');
    $previewExt = strtolower(pathinfo((string) $submission->original_filename, PATHINFO_EXTENSION));
    $submittedOn = optional($submission->submitted_at)?->format('M d, Y')
        ?? optional($submission->created_at)?->format('M d, Y')
        ?? '—';
    $stageLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->stage));
    $title = $submission->title ?: $stageLabel;
    $latestFeedback = $submission->feedback->first();
    $hideGroupFooter = $hideGroupFooter ?? false;
    $nestedInGroup = $nestedInGroup ?? false;
@endphp

<div class="card mb-3 border-0 shadow-sm submission-project-card {{ $nestedInGroup ? 'submission-project-card--nested border-bottom rounded-0 shadow-none mb-0' : '' }}">
    <div class="card-body">
        <div class="row g-3 g-lg-4 align-items-start">
            <div class="col-lg-5 col-xl-4">
                <div class="submission-project-card__interface">
                    @include('partials.project-interface-preview', [
                        'submission' => $submission,
                        'statusLabel' => $statusLabel,
                        'statusBadge' => $statusBadge,
                        'size' => 'md',
                    ])

                    <div class="submission-project-card__interface-footer mt-3 pt-3 border-top">
                        @unless ($hideGroupFooter)
                        <p class="small text-muted mb-2">
                            <i class="fas fa-user me-1" aria-hidden="true"></i>{{ $authors }}
                            <span class="mx-1">·</span>
                            {{ $submissionType }}
                            @if ($isGroup && $groupNo !== '—')
                                <span class="mx-1">·</span>{{ $groupNo }}
                            @endif
                        </p>
                        @endunless

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge {{ $statusBadge }}">
                                <i class="{{ $statusIcon }} me-1" aria-hidden="true"></i>
                                {{ $statusLabel }}
                            </span>

                            @if ($submission->file_path || $submission->isProjectShowcase())
                                @if ($submission->isProjectShowcase())
                                    <a href="{{ route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current()]) }}"
                                       class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0">
                                        <i class="fas fa-rocket me-1" aria-hidden="true"></i>Showcase
                                    </a>
                                @else
                                    <button type="button"
                                            class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0"
                                            data-bs-toggle="modal"
                                            data-bs-target="#prmsPreviewModal"
                                            data-preview-url="{{ route('student.submissions.preview', $submission) }}"
                                            data-download-url="{{ route('student.submissions.download', $submission) }}"
                                            data-file-name="{{ $submission->original_filename ?: $submission->title }}"
                                            data-mime-type="{{ $submission->mime_type }}"
                                            data-extension="{{ $previewExt }}"
                                            data-is-pdf="{{ $isPdf ? '1' : '0' }}">
                                        <i class="fas fa-eye me-1" aria-hidden="true"></i>View
                                    </button>
                                @endif
                                <span class="text-muted" aria-hidden="true">·</span>
                                <a href="{{ route('student.submissions.download', $submission) }}"
                                   class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0">
                                    <i class="fas fa-download me-1" aria-hidden="true"></i>Download
                                </a>
                                @if (! empty($showReview))
                                    <span class="text-muted" aria-hidden="true">·</span>
                                    <button type="button"
                                            class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0"
                                            data-bs-toggle="modal"
                                            data-bs-target="#prmsReviewModal-{{ $submission->id }}">
                                        <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i>Review
                                    </button>
                                @endif
                            @else
                                <span class="text-muted small">No file</span>
                            @endif
                        </div>

                        @if ($latestFeedback)
                            @php
                                $fbBadge = match ($latestFeedback->decision) {
                                    'approved'       => 'bg-success',
                                    'rejected'       => 'bg-danger',
                                    'needs_revision' => 'bg-warning text-dark',
                                    default          => 'bg-secondary',
                                };
                                $fbLabel = match ($latestFeedback->decision) {
                                    'approved'       => 'Approved',
                                    'rejected'       => 'Rejected',
                                    'needs_revision' => 'Returned for revision',
                                    default          => \Illuminate\Support\Str::title(str_replace('_', ' ', $latestFeedback->decision)),
                                };
                            @endphp
                            <div class="submission-project-card__feedback-snippet small rounded-3 p-2"
                                 style="background: var(--prms-surface-soft);">
                                <span class="fw-semibold text-strong d-block mb-1">
                                    <i class="far fa-comment-dots me-1 text-primary" aria-hidden="true"></i>
                                    Supervisor feedback
                                </span>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="badge {{ $fbBadge }}">{{ $fbLabel }}</span>
                                    <span class="text-muted">
                                        {{ optional($latestFeedback->supervisor)->name ?? 'Supervisor' }}
                                        <span class="mx-1">·</span>
                                        {{ $latestFeedback->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if ($latestFeedback->comments)
                                    <p class="mb-0 text-strong" style="line-height: 1.45; white-space: pre-line;">
                                        {{ \Illuminate\Support\Str::limit($latestFeedback->comments, 160) }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-xl-8">
                <h4 class="h6 fw-bold text-strong mb-1">{{ $title }}</h4>
                <div class="small text-muted mb-2">
                    <i class="far fa-calendar me-1" aria-hidden="true"></i>
                    {{ $submittedOn }}
                    <span class="mx-1">·</span>
                    <i class="fas fa-layer-group me-1" aria-hidden="true"></i>
                    {{ $stageLabel }}
                    <span class="mx-1">·</span>
                    v{{ $submission->version }}
                </div>
                @if ($submission->description)
                    <p class="small text-strong mb-0" style="line-height: 1.5;">
                        <i class="fas fa-quote-left text-faint me-1" aria-hidden="true"></i>
                        {{ \Illuminate\Support\Str::limit($submission->description, 320) }}
                    </p>
                @endif

                @if ($submission->feedback->count() > 1)
                    <div class="border rounded-3 p-3 mt-3" style="background: var(--prms-surface-soft);">
                        <h5 class="prms-eyebrow mb-2 d-flex align-items-center" style="color: var(--prms-color-info-500);">
                            <i class="far fa-comment-dots me-1" aria-hidden="true"></i>
                            Earlier supervisor feedback
                        </h5>
                        @foreach ($submission->feedback->skip(1) as $feedback)
                            @php
                                $fbBadge = match ($feedback->decision) {
                                    'approved'       => 'bg-success',
                                    'rejected'       => 'bg-danger',
                                    'needs_revision' => 'bg-warning text-dark',
                                    default          => 'bg-secondary',
                                };
                                $fbLabel = match ($feedback->decision) {
                                    'approved'       => 'Approved',
                                    'rejected'       => 'Rejected',
                                    'needs_revision' => 'Returned for revision',
                                    default          => \Illuminate\Support\Str::title(str_replace('_', ' ', $feedback->decision)),
                                };
                            @endphp
                            <div class="mb-3 pb-3 border-bottom last:mb-0 last:pb-0 last:border-0">
                                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                    <span class="badge {{ $fbBadge }}">{{ $fbLabel }}</span>
                                    <span class="small text-muted">
                                        <i class="far fa-user-circle me-1" aria-hidden="true"></i>
                                        {{ optional($feedback->supervisor)->name ?? 'Supervisor' }}
                                        <span class="mx-1">·</span>
                                        {{ $feedback->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="mb-0 text-strong" style="line-height: var(--prms-lh-base); white-space: pre-line;">{{ $feedback->comments }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
    <style>
        .submission-project-card__interface .prms-interface-preview--md {
            width: 100%;
            max-width: 100%;
            height: auto;
            aspect-ratio: 16 / 10;
            min-width: 0;
        }
    </style>
    @endpush
@endonce
