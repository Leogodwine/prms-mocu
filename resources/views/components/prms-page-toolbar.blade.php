@props([
    'subtitle' => null,
    'eyebrow' => null,
])

<div {{ $attributes->merge(['class' => 'prms-page-toolbar d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4']) }}>
    <div class="min-w-0 flex-grow-1">
        @if ($eyebrow)
            <span class="prms-eyebrow d-block mb-1">{{ $eyebrow }}</span>
        @endif
        @if ($subtitle)
            <p class="text-muted small mb-0" @if(isset($subtitleMax)) style="max-width: {{ $subtitleMax }};" @endif>{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim($slot) !== '')
        <div class="d-flex flex-wrap align-items-start justify-content-end gap-2 flex-shrink-0 align-self-start">
            {{ $slot }}
        </div>
    @endif
</div>
