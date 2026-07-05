@php
    $statusMessage = session('status') ?? session('success');
    $errors = $errors ?? session('errors') ?? new Illuminate\Support\ViewErrorBag();
    $toastQueue = [];

    if ($statusMessage) {
        $toastQueue[] = [
            'type' => 'success',
            'message' => $statusMessage,
            'html' => (bool) session('status_preformatted', false),
            'duration' => 7000,
        ];
    }

    $authInlineFieldKeys = ['login_id', 'password', 'email', 'login', 'token', 'current_password', 'password_confirmation'];
    $onlyInlineAuthErrors = $errors->any()
        && collect($errors->keys())->every(
            fn (string $key) => in_array($key, $authInlineFieldKeys, true)
        );

    $showValidationErrors = $errors->any()
        && ! in_array(old('form_context'), ['create', 'edit'], true)
        && ! $errors->has('delete')
        && ! $onlyInlineAuthErrors;

    $hasInlineAlerts = session('info')
        || session('warning')
        || session('error')
        || $errors->has('delete')
        || $showValidationErrors;
@endphp

@if ($toastQueue !== [])
    @push('prms-toast-queue')
        <script>
            window.__prmsToastQueue = window.__prmsToastQueue || [];
            @foreach ($toastQueue as $toast)
                window.__prmsToastQueue.push(@json($toast));
            @endforeach
        </script>
    @endpush
@endif

@if ($hasInlineAlerts)
    <div class="prms-flash-region" aria-live="polite" aria-atomic="true">
        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show d-flex align-items-start gap-2 mb-4" role="status">
                <i class="fas fa-info-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-grow-1">{{ session('info') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss info message"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2 mb-4" role="alert">
                <i class="fas fa-exclamation-triangle mt-1 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-grow-1">{{ session('warning') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss warning message"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 mb-4" role="alert">
                <i class="fas fa-times-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-grow-1">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss error message"></button>
            </div>
        @endif

        @if ($errors->has('delete'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 mb-4" role="alert">
                <i class="fas fa-exclamation-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-grow-1">{{ $errors->first('delete') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss error message"></button>
            </div>
        @endif

        @if ($showValidationErrors)
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 mb-4" role="alert">
                <i class="fas fa-exclamation-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-grow-1">
                    <p class="fw-semibold mb-2">Please correct the following:</p>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss validation message"></button>
            </div>
        @endif
    </div>
@endif
