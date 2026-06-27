@php
    $text = trim((string) ($text ?? ''));
    $collapseId = 'public-desc-'.($id ?? uniqid());
    $limit = (int) ($limit ?? 160);
    $isLong = strlen($text) > $limit;
    $preview = $isLong ? \Illuminate\Support\Str::limit($text, $limit, '…') : $text;
@endphp

@if ($text !== '')
    <div class="public-publication-desc {{ $class ?? '' }}">
        <p class="small text-strong mb-0 public-publication-desc__preview">
            @if (! empty($showQuoteIcon))
                <i class="fas fa-quote-left text-faint me-1" aria-hidden="true"></i>
            @endif
            {{ $preview }}
        </p>

        @if ($isLong)
            <button
                class="btn btn-link btn-sm text-decoration-none px-0 py-0 mt-1 d-inline-flex align-items-center gap-1 public-publication-desc__toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $collapseId }}"
                aria-expanded="false"
                aria-controls="{{ $collapseId }}">
                <span class="public-publication-desc__toggle-text">Read more</span>
                <i class="fas fa-chevron-down public-publication-desc__chevron small" aria-hidden="true"></i>
            </button>

            <div class="collapse public-publication-desc__full" id="{{ $collapseId }}">
                <p class="small text-strong mb-0 mt-2 public-publication-desc__body" style="white-space: pre-line; line-height: 1.55;">
                    {{ $text }}
                </p>
            </div>
        @endif
    </div>
@endif

@once
    @push('styles')
    <style>
        .public-publication-desc__preview {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }
        .public-publication-desc__chevron {
            transition: transform 0.2s ease;
        }
        .public-publication-desc__toggle[aria-expanded="true"] .public-publication-desc__chevron {
            transform: rotate(180deg);
        }
    </style>
    @endpush
    @push('scripts')
    <script>
    document.addEventListener('shown.bs.collapse', function (event) {
        const panel = event.target;
        if (!panel.classList.contains('public-publication-desc__full')) return;
        const toggle = document.querySelector('[data-bs-target="#' + panel.id + '"]');
        if (toggle) {
            toggle.querySelector('.public-publication-desc__toggle-text').textContent = 'Show less';
        }
    });
    document.addEventListener('hidden.bs.collapse', function (event) {
        const panel = event.target;
        if (!panel.classList.contains('public-publication-desc__full')) return;
        const toggle = document.querySelector('[data-bs-target="#' + panel.id + '"]');
        if (toggle) {
            toggle.querySelector('.public-publication-desc__toggle-text').textContent = 'Read more';
        }
    });
    </script>
    @endpush
@endonce
