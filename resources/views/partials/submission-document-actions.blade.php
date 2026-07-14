@php
    $isWord = in_array($previewExt ?? '', ['doc', 'docx'], true);
    $canEdit = ($context ?? 'student') === 'student'
        && auth()->check()
        && \App\Support\SubmissionFileAccess::canStudentEdit(auth()->user(), $submission);
    $canRemove = ($context ?? 'student') === 'student'
        && auth()->check()
        && \App\Support\SubmissionFileAccess::canStudentRemove(auth()->user(), $submission) === null;
    $editUrl = $canEdit
        ? (($isWord && ($onlyOfficeConfigured ?? false))
            ? route('student.submissions.editor', $submission)
            : \App\Support\SubmissionFileAccess::studentReplaceUrl($submission))
        : null;
    $actionsAlign = ($align ?? 'end') === 'start' ? 'start' : 'end';
    $compactActions = ($compactActions ?? false) || (($context ?? 'student') === 'student');
    $downloadLabel = $submission->original_filename ?: ($submission->title ?: 'document');
@endphp

@if ($submission->file_path)
    <div class="d-flex flex-wrap gap-1 justify-content-{{ $actionsAlign }} prms-submission-actions{{ $compactActions ? ' prms-submission-actions--compact' : '' }}">
        @if ($submission->isProjectShowcase())
            <a href="{{ route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current()]) }}"
               class="btn btn-primary btn-sm">
                <i class="fas fa-rocket me-1" aria-hidden="true"></i> Showcase
            </a>
        @endif

        @if ($canEdit && $editUrl)
            <a href="{{ $editUrl }}"
               class="btn btn-outline-secondary btn-sm{{ $compactActions ? ' px-2' : '' }}"
               @if($compactActions) aria-label="Edit {{ $downloadLabel }}" title="Edit" @endif>
                <i class="fas fa-pen{{ $compactActions ? '' : ' me-1' }}" aria-hidden="true"></i>
                @unless($compactActions) Edit @endunless
            </a>
        @endif

        @if ($isWord && ($onlyOfficeConfigured ?? false) && ($context ?? 'student') === 'supervisor')
            <a href="{{ route('student.submissions.editor', $submission) }}"
               class="btn btn-primary btn-sm">
                <i class="fas fa-file-word me-1" aria-hidden="true"></i> Review in Word
            </a>
        @elseif (! $isWord)
            <button type="button"
                    class="btn btn-outline-primary btn-sm{{ $compactActions ? ' px-2' : '' }}"
                    data-bs-toggle="modal"
                    data-bs-target="#prmsPreviewModal"
                    data-preview-url="{{ route('student.submissions.preview', $submission) }}"
                    data-download-url="{{ route('student.submissions.download', $submission) }}"
                    data-file-name="{{ $submission->original_filename ?: $submission->title }}"
                    data-mime-type="{{ $submission->mime_type }}"
                    data-extension="{{ $previewExt }}"
                    data-is-pdf="{{ ($isPdf ?? false) ? '1' : '0' }}"
                    @if($compactActions) aria-label="Preview {{ $downloadLabel }}" title="Preview" @endif>
                <i class="far fa-eye{{ $compactActions ? '' : ' me-1' }}" aria-hidden="true"></i>
                @unless($compactActions) Preview @endunless
            </button>
        @endif

        @php
            $docIcon = \App\Support\SubmissionFileAccess::documentIconMeta(
                $submission->mime_type,
                $submission->original_filename
            );
        @endphp
        <a href="{{ route('student.submissions.download', $submission) }}"
           class="btn btn-light btn-sm border{{ $compactActions ? ' px-2' : '' }}"
           @if($compactActions) aria-label="Download {{ $downloadLabel }}" title="Download {{ $docIcon['label'] }}" @endif>
            @if ($compactActions)
                <i class="{{ $docIcon['icon'] }} {{ $docIcon['class'] }}" aria-hidden="true"></i>
            @else
                <i class="fas fa-download me-1" aria-hidden="true"></i> Download
            @endif
        </a>

        @if ($canRemove)
            <form action="{{ route('student.submissions.destroy', $submission) }}" method="POST" class="m-0"
                  onsubmit="return confirm('Remove this submission? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm{{ $compactActions ? ' px-2' : '' }}"
                        @if($compactActions) aria-label="Remove {{ $downloadLabel }}" title="Remove" @endif>
                    <i class="fas fa-trash-alt{{ $compactActions ? '' : ' me-1' }}" aria-hidden="true"></i>
                    @unless($compactActions) Remove @endunless
                </button>
            </form>
        @endif

        @if (($context ?? 'student') === 'student' && ($submission->status ?? '') === 'draft')
            <form action="{{ route('student.submissions.submit-to-supervisor', $submission) }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Send this draft to your supervisor for review?')">
                    <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>
                    Submit to supervisor
                </button>
            </form>
        @elseif (($context ?? 'student') === 'student' && ($submission->status ?? '') === 'approved'
                && ! $submission->submitted_to_coordinator
                && \App\Support\StudentStageProgress::isCoordinatorEligibleStage((string) $submission->stage))
            @php
                $submitBlock = \App\Support\StudentStageProgress::canSubmitToCoordinator(
                    $submission,
                    auth()->user(),
                    auth()->user()->projectGroups()->first()
                );
            @endphp
            @if ($submitBlock === null)
            @php
                $completeLabel = \App\Support\StudentStageProgress::completeStageShortLabel(
                    \App\Support\StudentStageProgress::workTypeFromCompleteDocumentStage((string) $submission->stage)
                );
            @endphp
            <form action="{{ route('student.submissions.submit-to-coordinator', $submission) }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Send this approved {{ $completeLabel }} to the coordinator for final review?')">
                    <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>
                    Final submit to coordinator
                </button>
            </form>
            @elseif (\App\Support\RepositoryPublication::requiresConsentForStage((string) $submission->stage))
                <span class="badge bg-warning text-dark" title="{{ $submitBlock }}">
                    <i class="fas fa-lock me-1" aria-hidden="true"></i>
                    Awaiting supervisor consent
                </span>
            @endif
        @elseif (($context ?? 'student') === 'student' && $submission->submitted_to_coordinator
                && \App\Support\StudentStageProgress::isCoordinatorEligibleStage((string) $submission->stage))
            <span class="badge bg-info">
                <i class="fas fa-check me-1" aria-hidden="true"></i>
                Sent to coordinator
            </span>
            @if ($submission->coordinator_approved_at && ! $submission->repository_published_at
                && \App\Support\RepositoryPublication::requiresConsentForStage((string) $submission->stage))
                <span class="badge bg-warning text-dark">
                    <i class="fas fa-hourglass-half me-1" aria-hidden="true"></i>
                    Awaiting consent coordinator approval
                </span>
            @elseif ($submission->repository_published_at)
                <span class="badge bg-success">
                    <i class="fas fa-globe me-1" aria-hidden="true"></i>
                    In repository
                </span>
            @endif
        @elseif (($context ?? 'student') === 'student'
                && \App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage)
                && ($submission->status ?? '') === 'approved'
                && $submission->submitted_to_coordinator
                && ! $submission->coordinator_approved_at)
            <span class="badge bg-info">
                <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>
                Forwarded to coordinator
            </span>
        @elseif (($context ?? 'student') === 'student'
                && \App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage)
                && $submission->coordinator_approved_at)
            <span class="badge bg-success">
                <i class="fas fa-globe me-1" aria-hidden="true"></i>
                Consent finalized — repository release enabled
            </span>
        @endif
    </div>
@elseif (($context ?? 'student') === 'student'
        && \App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage))
    <div class="d-flex flex-wrap gap-1 justify-content-{{ $actionsAlign }} prms-submission-actions{{ $compactActions ? ' prms-submission-actions--compact' : '' }}">
        @if ($submission->presentation_date)
            <span class="badge bg-light text-dark border">
                <i class="far fa-calendar me-1" aria-hidden="true"></i>
                {{ $submission->presentation_date->format('d M Y') }}
            </span>
        @endif
        @if ($submission->supervisor_consent_signed_at)
            <a href="{{ route('student.presentation-consent.pdf', $submission) }}"
               class="btn btn-primary btn-sm"
               target="_blank" rel="noopener noreferrer">
                <i class="fas fa-file-pdf me-1" aria-hidden="true"></i>
                {{ $submission->coordinator_approved_at ? 'View finalized consent' : 'View supervisor-signed consent' }}
            </a>
        @endif
        @if (($submission->status ?? '') === 'needs_revision' || ($submission->status ?? '') === 'rejected')
            <span class="badge bg-warning text-dark">
                <i class="fas fa-undo me-1" aria-hidden="true"></i>
                {{ ($submission->status ?? '') === 'rejected' ? 'Rejected — resubmit required' : 'Returned for revision' }}
            </span>
        @elseif (($submission->status ?? '') === 'pending')
            <span class="badge bg-warning text-dark">
                <i class="far fa-clock me-1" aria-hidden="true"></i>
                Awaiting supervisor review
            </span>
        @elseif (($submission->status ?? '') === 'approved' && $submission->submitted_to_coordinator && ! $submission->coordinator_approved_at)
            <span class="badge bg-info">
                <i class="fas fa-paper-plane me-1" aria-hidden="true"></i>
                With coordinator for final sign-off
            </span>
        @elseif ($submission->coordinator_approved_at)
            <span class="badge bg-success">
                <i class="fas fa-check-circle me-1" aria-hidden="true"></i>
                Consent finalized
            </span>
        @endif
    </div>
@endif
