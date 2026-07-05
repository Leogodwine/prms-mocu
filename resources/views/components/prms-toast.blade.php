@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
    'duration' => 12000,
    'autohide' => true,
])

@php
    $payload = array_filter([
        'type' => $type,
        'title' => $title,
        'message' => $message ?? (trim($slot) !== '' ? trim($slot) : null),
        'duration' => (int) $duration,
        'autohide' => (bool) $autohide,
    ], fn ($value) => $value !== null && $value !== '');
@endphp

@if ($payload !== [] && ($payload['type'] ?? '') === 'success')
    @push('prms-toast-queue')
        <script>
            window.__prmsToastQueue = window.__prmsToastQueue || [];
            window.__prmsToastQueue.push(@json($payload));
        </script>
    @endpush
@endif
