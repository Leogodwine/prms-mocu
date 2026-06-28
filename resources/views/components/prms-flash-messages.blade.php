@php
    $statusMessage = session('status') ?? session('success');
@endphp

<div class="prms-flash-region" aria-live="polite" aria-atomic="true">
    @if ($statusMessage)
        @php $preformatted = (bool) session('status_preformatted', false); @endphp
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2 mb-4" role="status">
            <i class="fas fa-check-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1">
                @if ($preformatted)
                    <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $statusMessage }}</pre>
                @else
                    {{ $statusMessage }}
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss success message"></button>
        </div>
    @endif

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

    @if ($errors->any() && ! in_array(old('form_context'), ['create', 'edit'], true))
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-circle mt-1 flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1">
                <p class="fw-semibold mb-2">Please correct the following:</p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
