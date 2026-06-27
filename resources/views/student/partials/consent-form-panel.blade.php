@php
    $consentStageName = \App\Support\RepositoryPublication::consentStageName();
    $isConsentStage = $activeStage
        && \App\Support\StudentStageProgress::isConsentLetterStage((string) $activeStage->stage_name);
    $showConsentPanel = ($isProjectWorkspace ?? false)
        && ($isConsentStage || ($stages ?? collect())->contains(
            fn ($s) => \App\Support\StudentStageProgress::isConsentLetterStage($s->stage_name)
        ));
    $consentFormUrl = route('presentation-consent.show');
    $consentDownloadUrl = route('presentation-consent.download');
@endphp

@if ($showConsentPanel)
    <div class="modal fade" id="prmsConsentFormModal" tabindex="-1" aria-labelledby="prmsConsentFormModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0">
                <div class="modal-header border-bottom bg-brand-soft">
                    <div>
                        <h2 class="modal-title h5 fw-bold text-strong d-flex align-items-center gap-2 mb-1" id="prmsConsentFormModalTitle">
                            <i class="fas fa-file-signature text-primary" aria-hidden="true"></i>
                            Final presentation consent form
                        </h2>
                        <p class="text-muted small mb-0">
                            Download the consent form, then submit the <strong>{{ $consentStageName }}</strong> stage below.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Your supervisor signs and forwards it to the coordinator. Once the coordinator finalizes consent,
                        all approved complete documents for your group or individual work are published to the repository.
                    </p>

                    <form method="GET" action="{{ $consentFormUrl }}" class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold" for="prms-consent-date">Proposed presentation date</label>
                            <input type="date" id="prms-consent-date" name="presentation_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex flex-column gap-2 w-100">
                                <button type="submit"
                                        class="btn btn-outline-primary btn-sm w-100"
                                        formaction="{{ $consentFormUrl }}"
                                        formtarget="_blank">
                                    <i class="fas fa-eye me-1" aria-hidden="true"></i> Preview
                                </button>
                                <button type="submit"
                                        class="btn btn-primary btn-sm w-100"
                                        formaction="{{ $consentDownloadUrl }}">
                                    <i class="fas fa-download me-1" aria-hidden="true"></i> Download PDF
                                </button>
                            </div>
                        </div>
                    </form>

                    <ol class="small text-muted mb-0 ps-3">
                        <li>Enter your proposed presentation date, then download the consent form.</li>
                        <li>Submit the <strong>{{ $consentStageName }}</strong> stage using the upload form on this page.</li>
                        <li>Your supervisor signs and forwards it to the coordinator for final approval.</li>
                        <li>After coordinator approval, complete documents for your group or individual work are published to the repository.</li>
                    </ol>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
