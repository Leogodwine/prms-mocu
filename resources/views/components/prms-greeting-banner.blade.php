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

<div {{ $attributes->merge(['class' => 'card mb-4 border-0 overflow-hidden']) }}>
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3"
         style="border: 1px solid var(--prms-border); border-left: 4px solid var(--prms-color-danger-500);">
        <div class="flex-grow-1 min-w-0">
            <span class="prms-eyebrow d-block mb-1">{{ $eyebrowText }}</span>
            @if ($titleText)
                <h2 class="h4 fw-bold text-strong mb-1">{{ $titleText }}</h2>
            @endif
            @if ($subtitle)
                <p class="text-muted small mb-0" style="max-width: {{ $subtitleMax }};">{{ $subtitle }}</p>
            @endif
            @if (isset($meta))
                <div class="mt-2">{{ $meta }}</div>
            @endif
        </div>
        @if (trim($slot) !== '')
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-lg-auto flex-shrink-0">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
