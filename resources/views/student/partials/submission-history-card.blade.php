@php
    $statusBadge = match ($submission->status) {
        'approved'       => 'bg-success',
        'rejected'       => 'bg-danger',
        'needs_revision' => 'bg-warning text-dark',
        'pending'        => 'bg-warning text-dark',
        'draft'          => 'bg-secondary',
        default          => 'bg-secondary',
    };
    $statusIcon = match ($submission->status) {
        'approved'       => 'fas fa-check-circle',
        'rejected'       => 'fas fa-times-circle',
        'needs_revision' => 'fas fa-undo',
        'pending'        => 'far fa-clock',
        'draft'          => 'far fa-file',
        default          => 'far fa-question-circle',
    };
    $statusLabel = match ($submission->status) {
        'approved'       => 'Approved',
        'rejected'       => 'Rejected',
        'needs_revision' => 'Returned for revision',
        'pending'        => 'Awaiting review',
        'draft'          => 'Draft — ready to submit',
        default          => \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->status)),
    };
    $isPdf = str_contains((string) $submission->mime_type, 'pdf')
        || str_ends_with(strtolower((string) $submission->original_filename), '.pdf');
    $previewExt = strtolower(pathinfo((string) $submission->original_filename, PATHINFO_EXTENSION));
    $nestedInGroup = $nestedInGroup ?? false;
    $docIcon = \App\Support\SubmissionFileAccess::documentIconMeta(
        $submission->mime_type,
        $submission->original_filename
    );
@endphp

<div class="card mb-3 {{ ($nestedInGroup ?? false) ? 'border-0 border-bottom rounded-0 shadow-none mb-0' : '' }}">
    <div class="card-body {{ ($nestedInGroup ?? false) ? 'px-0 pt-0' : '' }}">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex gap-3 align-items-start">
                @if ($submission->isProjectShowcase())
                    @include('partials.project-interface-preview', [
                        'submission' => $submission,
                        'statusLabel' => $statusLabel,
                        'statusBadge' => $statusBadge,
                        'size' => 'md',
                    ])
                @else
                    @include('partials.submission-document-thumb', [
                        'submission' => $submission,
                        'size' => 'md',
                    ])
                @endif
                <div>
                    <h4 class="h6 fw-bold text-strong mb-1">{{ $submission->title }}</h4>
                    <div class="small text-muted">
                        <i class="far fa-calendar me-1" aria-hidden="true"></i>
                        {{ $submission->created_at->format('M d, Y') }}
                        <span class="mx-1">·</span>
                        <i class="fas fa-layer-group me-1" aria-hidden="true"></i>
                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->stage)) }}
                    </div>
                    @if ($submission->description)
                        <p class="small text-strong mt-2 mb-0" style="max-width: 56ch; line-height: 1.45;">
                            <i class="fas fa-quote-left text-faint me-1" aria-hidden="true"></i>
                            {{ \Illuminate\Support\Str::limit($submission->description, 220) }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-column align-items-end gap-2">
                <span class="badge {{ $statusBadge }}">
                    <i class="{{ $statusIcon }} me-1" aria-hidden="true"></i>
                    {{ $statusLabel }}
                </span>

                @if ($submission->file_path)
                    @include('partials.submission-document-actions', [
                        'submission' => $submission,
                        'previewExt' => $previewExt,
                        'isPdf' => $isPdf,
                        'statusLabel' => $statusLabel,
                        'statusBadge' => $statusBadge,
                        'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
                        'context' => 'student',
                        'compactActions' => true,
                    ])
                @endif
            </div>
        </div>

        @if ($submission->feedback->isNotEmpty())
            <div class="border rounded-3 p-3 mt-3" style="background: var(--prms-surface-soft);">
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

                @if ($submission->status === 'needs_revision')
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-0 mt-2" role="status">
                        <i class="fas fa-redo-alt mt-1" aria-hidden="true"></i>
                        <div class="flex-grow-1">
                            <strong>Action required:</strong>
                            update your document based on the comments above and submit a new version
                            from the upload form at the top of this page.
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
