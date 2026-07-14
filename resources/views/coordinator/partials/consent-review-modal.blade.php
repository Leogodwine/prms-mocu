@php
    $context = \App\Support\PresentationConsentForm::resolveFromSubmission($consent, auth()->user());
    extract($context, EXTR_SKIP);
    $modalId = 'prmsCoordinatorConsentModal-'.$consent->id;
    $isFinalized = $consent->coordinator_approved_at !== null;
    $signedOn = optional($consent->supervisor_consent_signed_at)?->format('d M Y, H:i') ?? '—';
@endphp

<div class="modal fade prms-coordinator-consent-modal"
     id="{{ $modalId }}"
     tabindex="-1"
     aria-labelledby="{{ $modalId }}-title"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h2 class="modal-title h5 fw-bold text-strong mb-1" id="{{ $modalId }}-title">
                        <i class="fas fa-file-signature text-primary me-2" aria-hidden="true"></i>
                        Supervisor confirmation form review
                    </h2>
                    <p class="text-muted small mb-0">
                        {{ $projectTitle ?? 'Project' }}
                        @if ($projectGroup?->name)
                            · {{ $projectGroup->name }}
                        @endif
                    </p>
                </div>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-4 border-end prms-coordinator-consent-sidebar p-4">
                        <p class="prms-eyebrow mb-3">Quick summary</p>
                        <ul class="list-unstyled small mb-4">
                            <li class="d-flex justify-content-between py-2 border-bottom gap-3">
                                <span class="text-muted">Presentation date</span>
                                <span class="fw-semibold text-strong text-end">
                                    {{ optional($consent->presentation_date)?->format('d M Y') ?? '—' }}
                                </span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-3">
                                <span class="text-muted">Supervisor</span>
                                <span class="fw-semibold text-strong text-end">{{ $supervisor?->name ?? '—' }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom gap-3">
                                <span class="text-muted">Signed</span>
                                <span class="fw-semibold text-strong text-end">{{ $signedOn }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 gap-3">
                                <span class="text-muted">Coordinator</span>
                                <span class="fw-semibold text-end">
                                    @if ($isFinalized)
                                        <span class="badge bg-success">Finalized</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </span>
                            </li>
                        </ul>
                        <div class="d-grid gap-2">
                            <a href="{{ route('coordinator.submissions.consent.pdf', $consent) }}"
                               class="btn btn-primary btn-sm rounded-pill"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-pdf me-1" aria-hidden="true"></i>
                                Open signed PDF
                            </a>
                        </div>
                        @if (\App\Support\RepositoryPublication::requiresConsentForStage((string) ($relatedStage ?? '')))
                            <p class="small text-muted mt-3 mb-0">
                                Finalizing this consent publishes approved complete project documents to the repository.
                            </p>
                        @endif
                    </div>
                    <div class="col-lg-8 p-4 p-md-5 bg-white">
                        <div class="prms-coordinator-consent-preview mx-auto">
                            @include('documents.partials.presentation-consent-body')
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top flex-wrap gap-2">
                <button type="button"
                        class="btn btn-light border rounded-pill px-4"
                        data-bs-dismiss="modal">
                    Close
                </button>
                @if (! $isFinalized)
                    <a href="{{ route('coordinator.submissions.consent.sign', $consent) }}"
                       class="btn btn-success rounded-pill px-4 fw-semibold ms-sm-auto">
                        <i class="fas fa-file-signature me-1" aria-hidden="true"></i>
                        Sign &amp; finalize
                    </a>
                    <button type="button"
                            class="btn btn-outline-danger rounded-pill px-4"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $modalId }}-reject">
                        <i class="fas fa-undo me-1" aria-hidden="true"></i>
                        Reject / return
                    </button>
                @else
                    <span class="text-success small fw-semibold ms-sm-auto">
                        <i class="fas fa-check-circle me-1" aria-hidden="true"></i>
                        Consent finalized
                    </span>
                @endif
            </div>
            @if (! $isFinalized)
                <div class="collapse border-top" id="{{ $modalId }}-reject">
                    <div class="p-4">
                        <form method="POST" action="{{ route('coordinator.submissions.consent.review', $consent) }}">
                            @csrf
                            <label for="{{ $modalId }}-comments" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                            <textarea id="{{ $modalId }}-comments"
                                      name="comments"
                                      rows="3"
                                      class="form-control mb-3"
                                      required
                                      placeholder="Explain what must be corrected before consent can be finalized…"></textarea>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <button type="submit" name="decision" value="needs_revision" class="btn btn-warning rounded-pill px-4">
                                    Return to student
                                </button>
                                <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger rounded-pill px-4">
                                    Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@once
    @push('styles')
    <style>
        .prms-coordinator-consent-modal .modal-dialog {
            max-width: min(1140px, 96vw);
        }
        .prms-coordinator-consent-modal .modal-content {
            background-color: #ffffff;
            color: var(--prms-musaris-heading, #17082d);
            border: 1px solid var(--prms-border, #e7eaec);
            box-shadow: var(--prms-shadow-lg, 0 24px 48px rgba(23, 8, 45, 0.18));
        }
        .prms-coordinator-consent-modal .modal-header,
        .prms-coordinator-consent-modal .modal-footer {
            background-color: var(--prms-color-neutral-50, #fafafa);
        }
        .prms-coordinator-consent-modal .modal-title {
            color: var(--prms-musaris-heading, #17082d);
        }
        .prms-coordinator-consent-sidebar {
            background: linear-gradient(180deg, #f0f6ff 0%, #fafafa 100%);
        }
        .prms-coordinator-consent-preview {
            max-width: 720px;
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 0.75rem;
            box-shadow: 0 8px 24px rgba(10, 43, 107, 0.08);
            padding: 1.5rem 1.25rem;
        }
        .prms-coordinator-consent-preview .prms-consent-doc {
            max-width: 100%;
            font-size: 10.5pt;
            color: #111111;
        }
        .prms-coordinator-consent-preview .prms-consent-doc .uni-heading,
        .prms-coordinator-consent-preview .prms-consent-doc .uni-heading-sw {
            color: #0a2b6b;
        }
        .prms-coordinator-consent-preview .prms-consent-doc .prms-consent-letterhead__logo img {
            max-height: 56px;
        }
    </style>
    @endpush
@endonce
