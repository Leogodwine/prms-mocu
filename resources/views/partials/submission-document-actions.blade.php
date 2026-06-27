@php
    $isWord = in_array($previewExt ?? '', ['doc', 'docx'], true);
    $canEdit = ($context ?? 'student') === 'student'
        && auth()->check()
        && \App\Support\SubmissionFileAccess::canStudentEdit(auth()->user(), $submission);
    $actionsAlign = ($align ?? 'end') === 'start' ? 'start' : 'end';
@endphp

@if ($submission->file_path)
    <div class="d-flex flex-wrap gap-2 justify-content-{{ $actionsAlign }}">
        @if ($submission->isProjectShowcase())
            <a href="{{ route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current()]) }}"
               class="btn btn-primary btn-sm">
                <i class="fas fa-rocket me-1" aria-hidden="true"></i> Showcase
            </a>
        @endif

        @if ($isWord && ($onlyOfficeConfigured ?? false))
            <a href="{{ route('student.submissions.editor', $submission) }}"
               class="btn {{ ($context ?? 'student') === 'supervisor' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                <i class="fas fa-file-word me-1" aria-hidden="true"></i>
                @if ($canEdit)
                    Edit in Word
                @elseif (($context ?? 'student') === 'supervisor')
                    Review in Word
                @else
                    Open in Word
                @endif
            </a>
        @elseif (! $isWord)
            <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#prmsPreviewModal"
                    data-preview-url="{{ route('student.submissions.preview', $submission) }}"
                    data-download-url="{{ route('student.submissions.download', $submission) }}"
                    data-file-name="{{ $submission->original_filename ?: $submission->title }}"
                    data-mime-type="{{ $submission->mime_type }}"
                    data-extension="{{ $previewExt }}"
                    data-is-pdf="{{ ($isPdf ?? false) ? '1' : '0' }}">
                <i class="far fa-eye me-1" aria-hidden="true"></i> Preview
            </button>
        @endif

        <a href="{{ route('student.submissions.download', $submission) }}" class="btn btn-light btn-sm border">
            <i class="fas fa-download me-1" aria-hidden="true"></i> Download
        </a>

        @if (($context ?? 'student') === 'student' && ($submission->status ?? '') === 'approved'
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
@endif
