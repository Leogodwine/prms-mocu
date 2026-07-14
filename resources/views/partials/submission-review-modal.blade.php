<div class="modal fade" id="prmsReviewModal-{{ $submission->id }}" tabindex="-1"
     aria-labelledby="prmsReviewModalTitle-{{ $submission->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header border-bottom">
                <div>
                    <h2 class="modal-title h5 fw-bold text-strong" id="prmsReviewModalTitle-{{ $submission->id }}">
                        <i class="fas fa-clipboard-check text-primary me-2" aria-hidden="true"></i>
                        Review submission
                    </h2>
                    <p class="text-muted small mb-0">{{ $submission->title }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <p class="prms-eyebrow mb-2">Submission details</p>
                        <ul class="list-unstyled small mb-0">
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Stage</span>
                                <span class="fw-semibold text-strong text-end">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $submission->stage)) }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Version</span>
                                <span class="fw-semibold text-strong">v{{ $submission->version }}</span>
                            </li>
                            <li class="d-flex justify-content-between py-2">
                                <span class="text-muted">Received</span>
                                <span class="fw-semibold text-strong">{{ $submission->created_at->format('M d, H:i') }}</span>
                            </li>
                        </ul>
                        <a href="{{ route('supervisor.evaluate', $submission) }}" class="btn btn-sm btn-outline-primary w-100 mt-3">
                            <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i>
                            Apply grading scheme
                        </a>
                    </div>
                    <div class="col-md-8">
                        @if (\App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage))
                            <div class="alert alert-warning d-flex align-items-start gap-2 small mb-3" role="note">
                                <i class="fas fa-file-signature mt-1" aria-hidden="true"></i>
                                <div>
                                    <strong>Consent letter.</strong> Review the proposed presentation date, complete the required
                                    fields, sign digitally, and send the signed consent back to the student. It is also forwarded
                                    to the coordinator for final sign-off.
                                </div>
                            </div>
                        @endif
                        @if (\App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage))
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="{{ route('supervisor.presentation-consent.sign', $submission) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-signature me-1" aria-hidden="true"></i>
                                    Sign consent &amp; preview PDF
                                </a>
                                <a href="{{ route('presentation-consent.show', ['submission' => $submission->id]) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-eye me-1" aria-hidden="true"></i>
                                    View printable form
                                </a>
                            </div>
                        @endif
                        @if (! \App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage))
                        <p class="prms-eyebrow mb-2">Review decision</p>
                        <form action="{{ route('supervisor.review', $submission) }}" method="POST" id="prmsReviewForm-{{ $submission->id }}">
                            @csrf
                            <input type="hidden" name="_submission_id" value="{{ $submission->id }}">
                            @if (! empty($redirectTo))
                                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                            @endif
                            <div class="mb-3">
                                <label for="comments-{{ $submission->id }}" class="form-label">Feedback comments <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea
                                    id="comments-{{ $submission->id }}"
                                    name="comments"
                                    rows="4"
                                    placeholder="Provide constructive feedback or mention required changes…"
                                    class="form-control @if ($errors->has('comments') && (int) old('_submission_id') === $submission->id) is-invalid @endif">{{ (int) old('_submission_id') === $submission->id ? old('comments') : '' }}</textarea>
                                @if ($errors->has('comments') && (int) old('_submission_id') === $submission->id)
                                    <div class="invalid-feedback d-block">{{ $errors->first('comments') }}</div>
                                @endif
                            </div>
                            <div class="d-flex flex-column gap-2 align-items-stretch">
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    <button type="submit" name="decision" value="needs_revision" class="btn btn-warning">
                                        <i class="fas fa-undo me-1" aria-hidden="true"></i>
                                        Return for revision
                                    </button>
                                    <button type="submit" name="decision" value="approved" class="btn btn-success">
                                        <i class="fas fa-check-circle me-1" aria-hidden="true"></i>
                                        {{ \App\Support\StudentStageProgress::isConsentLetterStage((string) $submission->stage) ? 'Sign & forward to coordinator' : 'Approve stage' }}
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="decision" value="rejected"
                                            class="btn btn-light border text-danger"
                                            onclick="return confirm('Reject this submission outright? The student will need to start the stage from scratch.');">
                                        <i class="fas fa-times-circle me-1" aria-hidden="true"></i>
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </form>
                        @else
                        <p class="prms-eyebrow mb-2 mt-3">Revision or rejection</p>
                        <form action="{{ route('supervisor.review', $submission) }}" method="POST" id="prmsReviewForm-{{ $submission->id }}">
                            @csrf
                            <input type="hidden" name="_submission_id" value="{{ $submission->id }}">
                            @if (! empty($redirectTo))
                                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                            @endif
                            <div class="mb-3">
                                <label for="comments-consent-{{ $submission->id }}" class="form-label">Reason for rejection or revision <span class="text-danger">*</span></label>
                                <textarea
                                    id="comments-consent-{{ $submission->id }}"
                                    name="comments"
                                    rows="4"
                                    required
                                    placeholder="Explain what must be revised or why the consent request is rejected…"
                                    class="form-control @if ($errors->has('comments') && (int) old('_submission_id') === $submission->id) is-invalid @endif">{{ (int) old('_submission_id') === $submission->id ? old('comments') : '' }}</textarea>
                                @if ($errors->has('comments') && (int) old('_submission_id') === $submission->id)
                                    <div class="invalid-feedback d-block">{{ $errors->first('comments') }}</div>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="decision" value="needs_revision" class="btn btn-warning">
                                    <i class="fas fa-undo me-1" aria-hidden="true"></i>
                                    Return for revision
                                </button>
                                <button type="submit" name="decision" value="rejected"
                                        class="btn btn-light border text-danger"
                                        onclick="return confirm('Reject this submission outright? The student will need to start the stage from scratch.');">
                                    <i class="fas fa-times-circle me-1" aria-hidden="true"></i>
                                    Reject
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
