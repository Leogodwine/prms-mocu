@extends('layouts.app')

@section('title', 'Sign supervisor confirmation form')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10 col-xxl-9">
            <x-prms-greeting-banner subtitle="Review the MoCU supervisor confirmation form, sign, and preview the PDF before forwarding to the coordinator.">
                <a href="{{ route('supervisor.index') }}" class="btn btn-light border rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to queue
                </a>
            </x-prms-greeting-banner>

            @error('pdf')
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i> {{ $message }}
                </div>
            @enderror

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 text-center">
                    <h2 class="h6 fw-bold text-strong mb-1">
                        <i class="fas fa-file-signature text-primary me-2" aria-hidden="true"></i>
                        Final presentation — supervisor confirmation form
                    </h2>
                    <p class="small text-muted mb-0">{{ $submission->title }} · v{{ $submission->version }}</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="mx-auto" style="max-width: 720px;">
                        @include('documents.partials.presentation-consent-body')
                    </div>
                </div>
            </div>

            @if ($alreadySigned)
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <div class="alert alert-success d-inline-flex align-items-center gap-2 mb-3" role="status">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            Consent signed and forwarded to the coordinator.
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('supervisor.presentation-consent.pdf', $submission) }}"
                               class="btn btn-primary rounded-pill px-4"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-pdf me-1" aria-hidden="true"></i> View signed PDF
                            </a>
                            <a href="{{ route('supervisor.index') }}" class="btn btn-light border rounded-pill px-4">
                                Return to queue
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h3 class="h6 fw-bold text-strong mb-0 text-center">Supervisor signature</h3>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST"
                              action="{{ route('supervisor.presentation-consent.sign.store', $submission) }}"
                              id="prms-consent-sign-form"
                              class="mx-auto"
                              style="max-width: 640px;">
                            @csrf

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="consent_group_number" class="form-label fw-semibold">Group number</label>
                                    <input type="text"
                                           id="consent_group_number"
                                           name="consent_group_number"
                                           class="form-control @error('consent_group_number') is-invalid @enderror"
                                           value="{{ old('consent_group_number', $groupNumber ?? '') }}"
                                           required>
                                    @error('consent_group_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="presentation_date" class="form-label fw-semibold">Proposed presentation date</label>
                                    <input type="date"
                                           id="presentation_date"
                                           name="presentation_date"
                                           class="form-control @error('presentation_date') is-invalid @enderror"
                                           value="{{ old('presentation_date', optional($submission->presentation_date)->format('Y-m-d') ?: ($presentationDateRaw ?? '')) }}"
                                           required>
                                    @error('presentation_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="consent_project_title" class="form-label fw-semibold">Project title</label>
                                    <input type="text"
                                           id="consent_project_title"
                                           name="consent_project_title"
                                           class="form-control @error('consent_project_title') is-invalid @enderror"
                                           value="{{ old('consent_project_title', $submission->consent_project_title ?: (str_contains((string) ($projectTitle ?? ''), '____') ? '' : ($projectTitle ?? ''))) }}"
                                           required>
                                    @error('consent_project_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('consent_agreed') is-invalid @enderror"
                                       type="checkbox"
                                       value="1"
                                       id="consent_agreed"
                                       name="consent_agreed"
                                       @checked(old('consent_agreed'))>
                                <label class="form-check-label" for="consent_agreed">
                                    I confirm that I supervise the group or student named in this form, that their ICT
                                    project is ready for the final presentation, and I accept them to present their project
                                    as stated on the MoCU supervisor confirmation form.
                                </label>
                                @error('consent_agreed')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block text-center">Draw your signature</label>
                                <div class="border rounded bg-white mx-auto position-relative" style="max-width: 480px;">
                                    <canvas id="prms-signature-pad"
                                            width="480"
                                            height="160"
                                            class="d-block w-100"
                                            style="touch-action: none; cursor: crosshair;"></canvas>
                                </div>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light border" id="prms-signature-clear">
                                        <i class="fas fa-eraser me-1" aria-hidden="true"></i> Clear signature
                                    </button>
                                </div>
                                <input type="hidden" name="signature" id="prms-signature-data" value="{{ old('signature') }}">
                                @error('signature')
                                    <div class="text-danger small text-center mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="comments" class="form-label fw-semibold">Feedback comments <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea id="comments"
                                          name="comments"
                                          rows="3"
                                          class="form-control @error('comments') is-invalid @enderror"
                                          placeholder="Optional notes for the student or coordinator…">{{ old('comments') }}</textarea>
                                @error('comments')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="button" class="btn btn-outline-primary rounded-pill px-4" id="prms-preview-pdf">
                                    <i class="fas fa-eye me-1" aria-hidden="true"></i> Preview PDF
                                </button>
                                <button type="submit" class="btn btn-success rounded-pill px-4">
                                    <i class="fas fa-file-signature me-1" aria-hidden="true"></i>
                                    Sign &amp; send to student &amp; coordinator
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <form method="POST"
                              action="{{ route('supervisor.review', $submission) }}"
                              class="mx-auto"
                              style="max-width: 640px;"
                              onsubmit="return confirm('Return or reject this consent request? The student will be notified with your reason.');">
                            @csrf
                            <p class="fw-semibold text-center mb-3">Reject or return for revision</p>
                            <div class="mb-3">
                                <label for="consent-reject-comments" class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                                <textarea id="consent-reject-comments"
                                          name="comments"
                                          rows="3"
                                          class="form-control"
                                          required
                                          placeholder="Explain what must be corrected or why the consent request is rejected…"></textarea>
                            </div>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="submit" name="decision" value="needs_revision" class="btn btn-warning rounded-pill px-4">
                                    <i class="fas fa-undo me-1" aria-hidden="true"></i> Return to student
                                </button>
                                <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger rounded-pill px-4">
                                    <i class="fas fa-times-circle me-1" aria-hidden="true"></i> Reject
                                </button>
                            </div>
                        </form>

                        <form method="POST"
                              action="{{ route('supervisor.presentation-consent.preview-pdf', $submission) }}"
                              id="prms-preview-pdf-form"
                              target="_blank"
                              class="d-none">
                            @csrf
                            <input type="hidden" name="signature" id="prms-preview-signature">
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

@unless ($alreadySigned ?? false)
    @push('scripts')
    <script>
        (function () {
            const canvas = document.getElementById('prms-signature-pad');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const hiddenInput = document.getElementById('prms-signature-data');
            const previewInput = document.getElementById('prms-preview-signature');
            const clearBtn = document.getElementById('prms-signature-clear');
            const previewBtn = document.getElementById('prms-preview-pdf');
            const signForm = document.getElementById('prms-consent-sign-form');
            const previewForm = document.getElementById('prms-preview-pdf-form');
            let drawing = false;
            let hasInk = false;

            function resizeCanvas() {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = Math.floor(rect.width * ratio);
                canvas.height = Math.floor(160 * ratio);
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#111';
            }

            function pointerPos(event) {
                const rect = canvas.getBoundingClientRect();
                const clientX = event.touches ? event.touches[0].clientX : event.clientX;
                const clientY = event.touches ? event.touches[0].clientY : event.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top,
                };
            }

            function startDraw(event) {
                event.preventDefault();
                drawing = true;
                const pos = pointerPos(event);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            }

            function draw(event) {
                if (!drawing) return;
                event.preventDefault();
                const pos = pointerPos(event);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                hasInk = true;
            }

            function endDraw() {
                drawing = false;
            }

            function signatureDataUri() {
                return canvas.toDataURL('image/png');
            }

            function syncHiddenFields() {
                const value = hasInk ? signatureDataUri() : '';
                hiddenInput.value = value;
                previewInput.value = value;
            }

            function isBlank() {
                return !hasInk;
            }

            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', endDraw);
            canvas.addEventListener('mouseleave', endDraw);
            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', endDraw);

            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasInk = false;
                hiddenInput.value = '';
                previewInput.value = '';
            });

            previewBtn.addEventListener('click', function () {
                if (isBlank()) {
                    alert('Please draw your signature before previewing the PDF.');
                    return;
                }
                syncHiddenFields();
                previewForm.submit();
            });

            signForm.addEventListener('submit', function (event) {
                if (isBlank()) {
                    event.preventDefault();
                    alert('Please draw your signature before submitting.');
                    return;
                }
                syncHiddenFields();
            });
        })();
    </script>
    @endpush
@endunless
@endsection
