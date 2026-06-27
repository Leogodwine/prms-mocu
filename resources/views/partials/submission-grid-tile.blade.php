@php
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
    $stageLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->stage));
    $displayTitle = $submission->title ?: $stageLabel;
@endphp

<div class="col">
    <article class="card h-100 border-0 shadow-sm submission-grid-tile">
        <div class="card-body d-flex flex-column p-4">
            <div class="submission-grid-tile__preview mb-3">
                @include('partials.project-interface-preview', [
                    'submission' => $submission,
                    'statusLabel' => $statusLabel,
                    'statusBadge' => $statusBadge,
                    'size' => 'md',
                ])
            </div>

            <h4 class="h5 fw-bold text-strong mb-2 submission-grid-tile__title" title="{{ $displayTitle }}">
                {{ $displayTitle }}
            </h4>
            <p class="small text-muted mb-3">
                <i class="far fa-calendar me-1" aria-hidden="true"></i>{{ $submission->created_at->format('M d, Y') }}
                <span class="mx-1">·</span>
                <i class="fas fa-layer-group me-1" aria-hidden="true"></i>{{ $stageLabel }}
                <span class="mx-1">·</span>v{{ $submission->version }}
            </p>

            @if ($submission->description && ! $submission->isProjectShowcase())
                <p class="small text-strong mb-3 submission-grid-tile__desc">
                    <i class="fas fa-quote-left text-faint me-1" aria-hidden="true"></i>
                    {{ \Illuminate\Support\Str::limit($submission->description, 180) }}
                </p>
            @endif

            <div class="mt-auto">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge {{ $statusBadge }}">
                        <i class="{{ $statusIcon }} me-1" aria-hidden="true"></i>{{ $statusLabel }}
                    </span>
                </div>

                @if ($submission->file_path || $submission->isProjectShowcase())
                    <div class="submission-grid-tile__actions mb-3">
                        @if ($submission->file_path)
                            @include('partials.submission-document-actions', [
                                'submission' => $submission,
                                'previewExt' => $previewExt,
                                'isPdf' => $isPdf,
                                'statusLabel' => $statusLabel,
                                'statusBadge' => $statusBadge,
                                'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
                                'context' => 'student',
                                'align' => 'start',
                            ])
                        @elseif ($submission->isProjectShowcase())
                            <a href="{{ route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current()]) }}"
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-rocket me-1" aria-hidden="true"></i>Showcase
                            </a>
                        @endif

                        @if (! empty($showReview))
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#prmsReviewModal-{{ $submission->id }}">
                                <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i>Review
                            </button>
                        @endif
                    </div>
                @endif

                @if ($submission->feedback->isNotEmpty())
                    <div class="border rounded-3 p-3 submission-grid-tile__feedback" style="background: var(--prms-surface-soft);">
                        <h5 class="prms-eyebrow mb-2 d-flex align-items-center" style="color: var(--prms-color-info-500);">
                            <i class="far fa-comment-dots me-1" aria-hidden="true"></i>
                            Supervisor feedback
                        </h5>
                        @foreach ($submission->feedback as $feedback)
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
                            <div class="mb-2 pb-2 border-bottom last:mb-0 last:pb-0 last:border-0">
                                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                    <span class="badge {{ $fbBadge }}">{{ $fbLabel }}</span>
                                    <span class="small text-muted">
                                        <i class="far fa-user-circle me-1" aria-hidden="true"></i>
                                        {{ optional($feedback->supervisor)->name ?? 'Supervisor' }}
                                        <span class="mx-1">·</span>
                                        {{ $feedback->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="mb-0 small text-strong" style="line-height: var(--prms-lh-base); white-space: pre-line;">{{ $feedback->comments }}</p>
                            </div>
                        @endforeach

                        @if ($submission->status === 'needs_revision')
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-0 mt-2 py-2 px-3" role="status">
                                <i class="fas fa-redo-alt mt-1" aria-hidden="true"></i>
                                <div class="flex-grow-1 small">
                                    <strong>Action required:</strong>
                                    update your document and submit a new version.
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </article>
</div>
