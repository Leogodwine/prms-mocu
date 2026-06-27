@extends('layouts.public')

@section('title', $submission->title ?: 'Project showcase')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item">
        <a href="{{ route('public.research.index') }}">{{ __('Repository') }}</a>
    </li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item">
        <a href="{{ route('public.research.show', $project) }}">{{ \Illuminate\Support\Str::limit($project->title, 40) }}</a>
    </li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">Showcase</span></li>
@endsection

@push('styles')
@include('partials.project-interface-preview-styles')
@endpush

@section('content')
@php
    $screenshotUrl = $submission->screenshot_path
        ? route('public.research.screenshot', $project)
        : null;
    $demoUrl = trim((string) ($submission->demo_url ?? ''));
    $videoUrl = trim((string) ($submission->video_url ?? ''));
    $videoEmbed = $submission->embeddable_video_url;
    $archiveUrl = route('public.research.source-download', $project);
    $documentUrl = route('public.research.download', $project);
@endphp

<div class="py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="badge bg-success mb-2">
                <i class="fas fa-globe me-1" aria-hidden="true"></i>Published in repository
            </span>
            <h1 class="h2 fw-bold text-strong mb-2">{{ $submission->title ?: $project->title }}</h1>
            <p class="text-muted mb-0">
                {{ $stageLabel }}
                <span class="mx-1">·</span>
                v{{ $submission->version }}
                @if ($project->published_at)
                    <span class="mx-1">·</span>{{ $project->published_at->format('M d, Y') }}
                @endif
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('public.research.show', $project) }}" class="btn btn-light border btn-sm">
                <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Publication details
            </a>
            @if ($demoUrl !== '')
                <button type="button" class="btn btn-outline-primary btn-sm" data-prms-showcase-demo>
                    <i class="fas fa-globe me-1" aria-hidden="true"></i> Live demo
                </button>
            @endif
            <a href="{{ $archiveUrl }}" class="btn btn-primary btn-sm">
                <i class="fas fa-file-archive me-1" aria-hidden="true"></i> Download source
            </a>
            <a href="{{ $documentUrl }}" class="btn btn-light border btn-sm">
                <i class="fas fa-download me-1" aria-hidden="true"></i> Complete document
            </a>
        </div>
    </div>

    @if ($submission->description)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold text-strong mb-2">About the system</h2>
                <p class="mb-0 text-strong" style="white-space: pre-line; line-height: 1.55;">{{ $submission->description }}</p>
            </div>
        </div>
    @endif

    @if ($screenshotUrl)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold text-strong mb-3">Home page interface</h2>
                @include('partials.project-interface-preview', [
                    'submission' => $submission,
                    'publicProject' => $project,
                    'size' => 'lg',
                    'onShowcasePage' => true,
                ])
            </div>
        </div>
    @endif

    @if ($demoUrl !== '')
        <div class="card border-0 shadow-sm mb-4 {{ ($openDemo ?? false) ? '' : 'd-none' }}" id="prms-showcase-demo-wrap">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                    <h2 class="h6 fw-bold text-strong mb-0">
                        <i class="fas fa-globe me-1 text-primary" aria-hidden="true"></i> Live demo
                    </h2>
                    <a href="{{ $demoUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1" aria-hidden="true"></i> Open in new tab
                    </a>
                </div>
                <div class="ratio ratio-16x9 border rounded-3 overflow-hidden bg-light">
                    <iframe id="prms-showcase-demo-frame"
                            title="Live demo"
                            src="{{ ($openDemo ?? false) ? $demoUrl : 'about:blank' }}"
                            data-demo-src="{{ $demoUrl }}"
                            allow="fullscreen"
                            loading="lazy"></iframe>
                </div>
            </div>
        </div>
    @endif

    @if ($videoUrl !== '')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold text-strong mb-3">
                    <i class="fas fa-play-circle me-1 text-primary" aria-hidden="true"></i> Video walkthrough
                </h2>
                @if ($videoEmbed)
                    <div class="ratio ratio-16x9 border rounded-3 overflow-hidden bg-light">
                        <iframe src="{{ $videoEmbed }}" title="Video walkthrough" allowfullscreen loading="lazy"></iframe>
                    </div>
                @else
                    <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                        <i class="fas fa-play-circle me-1" aria-hidden="true"></i> Watch video
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-prms-showcase-demo]');
    if (!trigger) return;

    const wrap = document.getElementById('prms-showcase-demo-wrap');
    const frame = document.getElementById('prms-showcase-demo-frame');
    if (!wrap || !frame) return;

    wrap.classList.remove('d-none');
    const src = frame.getAttribute('data-demo-src');
    if (src && frame.src === 'about:blank') {
        frame.src = src;
    }
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
@endpush
