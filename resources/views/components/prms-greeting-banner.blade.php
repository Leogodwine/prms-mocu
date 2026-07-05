@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'subtitleMax' => '48rem',
    'showHello' => true,
])

@php
    $eyebrowText = $eyebrow ?? \App\Support\PrmsGreeting::date();
    $titleText = $title;
    if ($titleText === null && $showHello && auth()->check()) {
        $titleText = \App\Support\PrmsGreeting::hello(auth()->user());
    }
@endphp

<div {{ $attributes->merge(['class' => 'card mb-4 border-0 overflow-hidden prms-greeting-banner']) }}>
    <div class="card-body p-4 prms-greeting-banner__body"
         style="border: 1px solid var(--prms-border); border-left: 4px solid var(--prms-color-danger-500);">
        <div class="prms-greeting-banner__content">
            <span class="prms-eyebrow d-block mb-1">{{ $eyebrowText }}</span>
            @if ($titleText)
                <h2 class="h4 fw-bold text-strong mb-1">{{ $titleText }}</h2>
            @endif
            @if ($subtitle)
                <p class="text-muted small mb-0" style="max-width: {{ $subtitleMax }};">{{ $subtitle }}</p>
            @endif
            @isset($meta)
                <div class="mt-2">{{ $meta }}</div>
            @endisset
        </div>
        @if (trim($slot) !== '')
            <div class="prms-greeting-banner__actions d-flex flex-wrap align-items-start justify-content-end gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>
    @isset($footer)
        <div class="prms-greeting-banner__footer border-top px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
