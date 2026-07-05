@php
    $docIcon = \App\Support\SubmissionFileAccess::documentIconMeta(
        $submission->mime_type,
        $submission->original_filename
    );
    $size = $size ?? 'md';
    $dim = match ($size) {
        'lg' => '56px',
        'sm' => '36px',
        default => '44px',
    };
    $fontSize = match ($size) {
        'lg' => '1.5rem',
        'sm' => '1rem',
        default => '1.25rem',
    };
@endphp

<div class="prms-doc-thumb rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-brand-soft border"
     style="width: {{ $dim }}; height: {{ $dim }};"
     title="{{ $docIcon['label'] }}">
    <i class="{{ $docIcon['icon'] }} {{ $docIcon['class'] }}"
       style="font-size: {{ $fontSize }};"
       aria-hidden="true"></i>
    <span class="visually-hidden">{{ $docIcon['label'] }}</span>
</div>
