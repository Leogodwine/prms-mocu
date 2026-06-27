@php
    $mainSubmissions = $group['main'];
@endphp

<section class="submission-project-grid-group mb-4">
    @if ($mainSubmissions->isNotEmpty())
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            @foreach ($mainSubmissions as $submission)
                @include('partials.submission-grid-tile', [
                    'submission' => $submission,
                    'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
                    'showReview' => $showReview ?? false,
                ])
            @endforeach
        </div>
    @endif
</section>

@once
    @push('styles')
    <style>
        .submission-grid-tile {
            min-height: 100%;
        }
        .submission-grid-tile__preview .prms-interface-preview {
            width: 100%;
            max-width: 100%;
            aspect-ratio: 16 / 10;
            min-height: 180px;
            min-width: 0;
        }
        .submission-grid-tile__title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 3rem;
            line-height: 1.35;
        }
        .submission-grid-tile__desc {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.45;
        }
        .submission-grid-tile__actions .btn {
            white-space: nowrap;
        }
        .submission-grid-tile__feedback {
            font-size: 0.875rem;
        }
    </style>
    @endpush
@endonce
