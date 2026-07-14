@php
    $latestConsent = $consentSubmissions->first();
    $statusBadge = match ($latestConsent->status) {
        'approved'       => 'bg-success',
        'rejected'       => 'bg-danger',
        'needs_revision' => 'bg-warning text-dark',
        'pending', 'submitted', 'under_review' => 'bg-warning text-dark',
        default          => 'bg-secondary',
    };
    $statusIcon = match ($latestConsent->status) {
        'approved'       => 'fas fa-check-circle',
        'rejected'       => 'fas fa-times-circle',
        'needs_revision' => 'fas fa-undo',
        'pending', 'submitted', 'under_review' => 'far fa-clock',
        default          => 'far fa-question-circle',
    };
    $statusLabel = match ($latestConsent->status) {
        'approved'       => 'Approved',
        'rejected'       => 'Rejected',
        'needs_revision' => 'Returned for revision',
        'pending'        => 'Awaiting review',
        'submitted'      => 'Submitted',
        'under_review'   => 'Under review',
        default          => \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $latestConsent->status)),
    };
    $collapseId = 'prms-consent-versions-'.$groupKey;
    $compactFooter = $compactFooter ?? false;
@endphp

<div class="submission-group-consent {{ $compactFooter ? '' : 'mt-3 pt-3 border-top' }}">
    <button
        class="btn btn-link text-decoration-none text-start w-100 d-flex align-items-center justify-content-between gap-2 px-0 py-1 submission-consent-toggle"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#{{ $collapseId }}"
        aria-expanded="false"
        aria-controls="{{ $collapseId }}">
        <span class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold text-strong">Consent form</span>
            <span class="badge {{ $statusBadge }}">
                <i class="{{ $statusIcon }} me-1" aria-hidden="true"></i>{{ $statusLabel }}
            </span>
            <span class="text-muted" aria-hidden="true">·</span>
            <span class="text-muted small">{{ $consentSubmissions->count() }} {{ $consentSubmissions->count() === 1 ? 'version' : 'versions' }}</span>
        </span>
        <i class="fas fa-chevron-down text-muted submission-consent-chevron" aria-hidden="true"></i>
    </button>

    <div class="collapse mt-2" id="{{ $collapseId }}">
        <div class="list-group list-group-flush border rounded-3 overflow-hidden bg-white">
            @foreach ($consentSubmissions as $consent)
                @php
                    $isPdf = str_contains((string) $consent->mime_type, 'pdf')
                        || str_ends_with(strtolower((string) $consent->original_filename), '.pdf');
                    $previewExt = strtolower(pathinfo((string) $consent->original_filename, PATHINFO_EXTENSION));
                    $submittedOn = optional($consent->submitted_at)?->format('M d, Y')
                        ?? optional($consent->created_at)?->format('M d, Y')
                        ?? '—';
                    $consentStatusBadge = match ($consent->status) {
                        'approved'       => 'bg-success',
                        'rejected'       => 'bg-danger',
                        'needs_revision' => 'bg-warning text-dark',
                        'pending', 'submitted', 'under_review' => 'bg-warning text-dark',
                        default          => 'bg-secondary',
                    };
                    $consentStatusLabel = match ($consent->status) {
                        'approved'       => 'Approved',
                        'rejected'       => 'Rejected',
                        'needs_revision' => 'Returned for revision',
                        'pending'        => 'Awaiting review',
                        default          => \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $consent->status)),
                    };
                @endphp
                <div class="list-group-item px-3 py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <h4 class="h6 fw-bold text-strong mb-1">{{ $consent->title ?: 'Consent form' }}</h4>
                            <div class="small text-muted">
                                <i class="far fa-calendar me-1" aria-hidden="true"></i>{{ $submittedOn }}
                                @if ($consent->presentation_date)
                                    <span class="mx-1">·</span>
                                    Presentation: {{ $consent->presentation_date->format('d M Y') }}
                                @endif
                                <span class="mx-1">·</span>
                                Final Presentation Consent Letter
                                <span class="mx-1">·</span>
                                v{{ $consent->version }}
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-2">
                            <span class="badge {{ $consentStatusBadge }}">
                                {{ $consentStatusLabel }}
                            </span>
                            <div class="d-flex flex-wrap align-items-center justify-content-end gap-1">
                                @if ($consent->file_path)
                                    <button type="button"
                                            class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0"
                                            data-bs-toggle="modal"
                                            data-bs-target="#prmsPreviewModal"
                                            data-preview-url="{{ route('student.submissions.preview', $consent) }}"
                                            data-download-url="{{ route('student.submissions.download', $consent) }}"
                                            data-file-name="{{ $consent->original_filename ?: $consent->title }}"
                                            data-mime-type="{{ $consent->mime_type }}"
                                            data-extension="{{ $previewExt }}"
                                            data-is-pdf="{{ $isPdf ? '1' : '0' }}">
                                        <i class="fas fa-eye me-1" aria-hidden="true"></i>View
                                    </button>
                                    <span class="text-muted" aria-hidden="true">·</span>
                                    <a href="{{ route('student.submissions.download', $consent) }}"
                                       class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0">
                                        <i class="fas fa-download me-1" aria-hidden="true"></i>Download
                                    </a>
                                @endif
                                @if ($consent->supervisor_consent_signed_at)
                                    <a href="{{ route(empty($showReview) ? 'student.presentation-consent.pdf' : 'supervisor.presentation-consent.pdf', $consent) }}"
                                       class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0"
                                       target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-file-pdf me-1" aria-hidden="true"></i>
                                        {{ $consent->coordinator_approved_at ? 'Final PDF' : 'Supervisor signed PDF' }}
                                    </a>
                                @endif
                                @if (! empty($showReview))
                                    <span class="text-muted" aria-hidden="true">·</span>
                                    @if (\App\Support\StudentStageProgress::isConsentLetterStage((string) $consent->stage)
                                        && $consent->status === 'approved'
                                        && $consent->supervisor_consent_signed_at)
                                        {{-- signed link shown above --}}
                                    @elseif ($consent->status === 'pending' || $consent->status === 'needs_revision')
                                        <a href="{{ route('supervisor.presentation-consent.sign', $consent) }}"
                                           class="btn btn-link btn-sm text-primary text-decoration-none px-1 py-0">
                                            <i class="fas fa-file-signature me-1" aria-hidden="true"></i>Sign
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@once
    @push('styles')
    <style>
        .submission-consent-toggle[aria-expanded="true"] .submission-consent-chevron {
            transform: rotate(180deg);
        }
        .submission-consent-chevron {
            transition: transform 0.2s ease;
        }
        .submission-project-group .submission-project-card--nested:last-of-type {
            margin-bottom: 0 !important;
            border-bottom: none !important;
        }
        .submission-project-group__footer {
            background: var(--prms-surface-soft, #f8fafc);
            border-radius: 0.75rem;
            padding: 1rem 1rem 0.5rem;
        }
    </style>
    @endpush
@endonce
