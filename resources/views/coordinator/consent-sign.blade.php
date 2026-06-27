@extends('layouts.app')

@section('title', 'Sign supervisor confirmation form')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10 col-xxl-9">
            <x-prms-greeting-banner subtitle="Review the supervisor-signed MoCU confirmation form, draw your signature, and finalize it for the repository.">
                <a href="{{ route('coordinator.submissions') }}" class="btn btn-light border rounded-pill px-3">
                    <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back to final submissions
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
                        Final presentation — coordinator confirmation
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
                            Confirmation form signed and finalized.
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('coordinator.submissions.consent.pdf', $submission) }}"
                               class="btn btn-primary rounded-pill px-4"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-file-pdf me-1" aria-hidden="true"></i> View signed PDF
                            </a>
                            <a href="{{ route('coordinator.submissions') }}" class="btn btn-light border rounded-pill px-4">
                                Return to final submissions
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h3 class="h6 fw-bold text-strong mb-0 text-center">Coordinator signature</h3>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST"
                              action="{{ route('coordinator.submissions.consent.sign.store', $submission) }}"
                              id="prms-coordinator-consent-sign-form"
                              class="mx-auto"
                              style="max-width: 640px;">
                            @csrf

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('consent_reviewed') is-invalid @enderror"
                                       type="checkbox"
                                       value="1"
                                       id="consent_reviewed"
                                       name="consent_reviewed"
                                       @checked(old('consent_reviewed'))>
                                <label class="form-check-label" for="consent_reviewed">
                                    I confirm that I have reviewed this supervisor confirmation form, including the
                                    supervisor&rsquo;s signature, and I accept the group or student to present their
                                    project as stated.
                                </label>
                                @error('consent_reviewed')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold d-block text-center">Draw your signature</label>
                                <div class="border rounded bg-white mx-auto position-relative" style="max-width: 480px;">
                                    <canvas id="prms-coordinator-signature-pad"
                                            width="480"
                                            height="160"
                                            class="d-block w-100"
                                            style="touch-action: none; cursor: crosshair;"></canvas>
                                </div>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light border" id="prms-coordinator-signature-clear">
                                        <i class="fas fa-eraser me-1" aria-hidden="true"></i> Clear signature
                                    </button>
                                </div>
                                <input type="hidden" name="signature" id="prms-coordinator-signature-data" value="{{ old('signature') }}">
                                @error('signature')
                                    <div class="text-danger small text-center mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button type="submit" class="btn btn-success rounded-pill px-4">
                                    <i class="fas fa-file-signature me-1" aria-hidden="true"></i>
                                    Sign &amp; finalize
                                </button>
                            </div>
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
            const canvas = document.getElementById('prms-coordinator-signature-pad');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const hiddenInput = document.getElementById('prms-coordinator-signature-data');
            const clearBtn = document.getElementById('prms-coordinator-signature-clear');
            const signForm = document.getElementById('prms-coordinator-consent-sign-form');
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

            function syncHiddenField() {
                hiddenInput.value = hasInk ? canvas.toDataURL('image/png') : '';
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
            });

            signForm.addEventListener('submit', function (event) {
                if (!hasInk) {
                    event.preventDefault();
                    alert('Please draw your signature before submitting.');
                    return;
                }
                syncHiddenField();
            });
        })();
    </script>
    @endpush
@endunless
@endsection
