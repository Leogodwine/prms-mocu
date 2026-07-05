@php
    $notificationToasts = \App\Support\PrmsNotificationToastQueue::build(request());
@endphp

@if ($notificationToasts !== [])
    @push('prms-toast-queue')
        <script>
            window.__prmsToastQueue = window.__prmsToastQueue || [];
            @foreach ($notificationToasts as $toast)
                window.__prmsToastQueue.push(@json($toast));
            @endforeach
        </script>
    @endpush
@endif
