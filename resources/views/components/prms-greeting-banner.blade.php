@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'subtitleMax' => '48rem',
    'showHello' => true,
    'actionsAlign' => 'center',
])

@php
    $eyebrowText = $eyebrow ?? \App\Support\PrmsGreeting::date();
    $titleText = $title;
    if ($titleText === null && $showHello && auth()->check()) {
        $titleText = \App\Support\PrmsGreeting::hello(auth()->user());
    }
    $bodyAlignClass = $actionsAlign === 'start' ? 'align-items-start' : 'align-items-center';
@endphp

<div {{ $attributes->merge(['class' => 'card mb-4 border-0 overflow-hidden']) }}>
    <div class="card-body p-4 d-flex flex-wrap justify-content-between gap-3 {{ $bodyAlignClass }}"
         style="border: 1px solid var(--prms-border); border-left: 4px solid var(--prms-color-danger-500);">
        <div class="flex-grow-1 min-w-0">
            <span class="prms-eyebrow d-block mb-1">{{ $eyebrowText }}</span>
            @if ($titleText)
                <h2 class="h4 fw-bold text-strong mb-1">{{ $titleText }}</h2>
            @endif
            @if ($subtitle)
                <p class="text-muted small mb-0" style="max-width: {{ $subtitleMax }};">{{ $subtitle }}</p>
            @endif
            @isset($meta)
                <div class="mt-0">{{ $meta }}</div>
            @endisset
        </div>
        @if (trim($slot) !== '')
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-lg-auto flex-shrink-0 w-100 w-lg-auto {{ $actionsAlign === 'start' ? 'align-self-lg-start' : 'align-self-lg-center' }}">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
