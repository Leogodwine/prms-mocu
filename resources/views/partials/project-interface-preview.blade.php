@php
    $size = $size ?? 'sm';
    $publicProject = $publicProject ?? null;
    $onShowcasePage = $onShowcasePage ?? false;

    $submission->loadMissing('interfaceScreenshots');
    $interfaceShots = $submission->interfaceScreenshots;
    $galleryId = 'prms-interface-gallery-'.($submission->id ?? uniqid());

    $demoUrl = trim((string) ($submission->demo_url ?? ''));
    $videoUrl = trim((string) ($submission->video_url ?? ''));
    $isShowcase = $submission->isProjectShowcase();
    $stageLabel = \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $submission->stage));

    if ($publicProject) {
        $downloadUrl = ($submission->file_path ?? null)
            ? route('public.research.source-download', $publicProject)
            : route('public.research.download', $publicProject);
    } else {
        $downloadUrl = $submission->file_path
            ? route('student.submissions.download', $submission)
            : null;
    }

    $showcaseUrl = $publicProject
        ? route('public.research.showcase', $publicProject)
        : route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current()]);

    $showcaseDemoUrl = $publicProject
        ? route('public.research.showcase', ['project' => $publicProject, 'open' => 'demo'])
        : route('student.submissions.showcase', ['submission' => $submission, 'return' => url()->current(), 'open' => 'demo']);

    $hasPreview = $interfaceShots->isNotEmpty() || $submission->screenshot_path || $isShowcase;
    $hasActions = $hasPreview || $demoUrl !== '' || $videoUrl !== '' || $downloadUrl;
@endphp

@if ($hasActions)
    <div class="prms-interface-preview prms-interface-preview--{{ $size }} flex-shrink-0" id="{{ $galleryId }}">
        @if ($interfaceShots->isNotEmpty())
            @foreach ($interfaceShots as $shot)
                @php
                    $shotUrl = $publicProject
                        ? route('public.research.screenshot', ['project' => $publicProject, 'interface' => $loop->index])
                        : route('student.submissions.interface-screenshot', $shot);
                @endphp
                <img src="{{ $shotUrl }}"
                     alt="{{ $shot->interface_name }} interface for {{ $submission->title ?: $stageLabel }}"
                     class="prms-interface-preview-image {{ $loop->first ? '' : 'd-none' }}"
                     data-interface-slide="{{ $loop->index }}">
            @endforeach
        @elseif ($submission->screenshot_path)
            @php
                $legacyUrl = $publicProject
                    ? route('public.research.screenshot', $publicProject)
                    : route('student.submissions.screenshot', $submission);
            @endphp
            <img src="{{ $legacyUrl }}"
                 alt="Home page interface for {{ $submission->title ?: $stageLabel }}"
                 class="prms-interface-preview-image">
        @else
            <div class="prms-interface-preview-placeholder" aria-hidden="true">
                <i class="fas fa-laptop-code"></i>
            </div>
        @endif

        @if ($interfaceShots->count() > 1)
            <div class="prms-interface-gallery-tabs">
                @foreach ($interfaceShots as $shot)
                    <button type="button"
                            class="prms-interface-gallery-tab {{ $loop->first ? 'is-active' : '' }}"
                            data-gallery-target="{{ $galleryId }}"
                            data-slide="{{ $loop->index }}"
                            aria-label="Show {{ $shot->interface_name }} screenshot">
                        {{ \Illuminate\Support\Str::limit($shot->interface_name, 18) }}
                    </button>
                @endforeach
            </div>
        @elseif ($interfaceShots->count() === 1)
            <div class="prms-interface-gallery-label">
                {{ $interfaceShots->first()->interface_name }}
            </div>
        @endif

        <div class="prms-interface-preview-overlay" role="group" aria-label="System actions">
            <div class="prms-interface-preview-actions">
                @if ($hasPreview)
                    @if ($onShowcasePage && ($interfaceShots->isNotEmpty() || $submission->screenshot_path))
                        <a href="{{ $interfaceShots->isNotEmpty() ? route('student.submissions.interface-screenshot', $interfaceShots->first()) : route('student.submissions.screenshot', $submission) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="prms-interface-preview-action prms-interface-preview-action--preview"
                           aria-label="Preview interface">
                            <span class="prms-interface-preview-action-icon">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                            <span class="prms-interface-preview-label">Preview</span>
                        </a>
                    @elseif ($isShowcase || $publicProject)
                        <a href="{{ $showcaseUrl }}"
                           class="prms-interface-preview-action prms-interface-preview-action--preview"
                           aria-label="Preview showcase">
                            <span class="prms-interface-preview-action-icon">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                            <span class="prms-interface-preview-label">Preview</span>
                        </a>
                    @else
                        <a href="{{ $interfaceShots->isNotEmpty() ? route('student.submissions.interface-screenshot', $interfaceShots->first()) : route('student.submissions.screenshot', $submission) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="prms-interface-preview-action prms-interface-preview-action--preview"
                           aria-label="Preview interface">
                            <span class="prms-interface-preview-action-icon">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                            <span class="prms-interface-preview-label">Preview</span>
                        </a>
                    @endif
                @endif

                @if ($demoUrl !== '')
                    @if ($onShowcasePage)
                        <button type="button"
                                class="prms-interface-preview-action prms-interface-preview-action--demo"
                                data-prms-showcase-demo
                                aria-label="Open live demo">
                            <span class="prms-interface-preview-action-icon">
                                <i class="fas fa-globe" aria-hidden="true"></i>
                            </span>
                            <span class="prms-interface-preview-label">Live demo</span>
                        </button>
                    @else
                        <a href="{{ $showcaseDemoUrl }}"
                           class="prms-interface-preview-action prms-interface-preview-action--demo"
                           aria-label="Open live demo">
                            <span class="prms-interface-preview-action-icon">
                                <i class="fas fa-globe" aria-hidden="true"></i>
                            </span>
                            <span class="prms-interface-preview-label">Live demo</span>
                        </a>
                    @endif
                @endif

                @if ($videoUrl !== '')
                    <a href="{{ $videoUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="prms-interface-preview-action prms-interface-preview-action--video"
                       aria-label="Video walkthrough">
                        <span class="prms-interface-preview-action-icon">
                            <i class="fas fa-play-circle" aria-hidden="true"></i>
                        </span>
                        <span class="prms-interface-preview-label">Video walkthrough</span>
                    </a>
                @endif

                @if ($downloadUrl)
                    <a href="{{ $downloadUrl }}"
                       class="prms-interface-preview-action prms-interface-preview-action--download"
                       aria-label="Download source archive">
                        <span class="prms-interface-preview-action-icon">
                            <i class="fas fa-download" aria-hidden="true"></i>
                        </span>
                        <span class="prms-interface-preview-label">Download</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="bg-brand-soft text-primary rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0 prms-interface-preview-fallback-icon"
         style="width: {{ $size === 'lg' ? '3rem' : '44px' }}; height: {{ $size === 'lg' ? '3rem' : '44px' }};">
        <i class="far fa-file-alt" aria-hidden="true"></i>
    </div>
@endif

@include('partials.project-interface-preview-styles')

@once
    @push('styles')
    <style>
        .prms-interface-gallery-tabs {
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            bottom: 0.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            z-index: 2;
        }
        .prms-interface-gallery-tab {
            border: 0;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.62rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.88);
            color: #0f172a;
            cursor: pointer;
        }
        .prms-interface-gallery-tab.is-active {
            background: var(--prms-primary, #1572E8);
            color: #fff;
        }
        .prms-interface-gallery-label {
            position: absolute;
            left: 0.5rem;
            bottom: 0.5rem;
            z-index: 2;
            background: rgba(255, 255, 255, 0.9);
            color: #0f172a;
            border-radius: 999px;
            padding: 0.2rem 0.6rem;
            font-size: 0.65rem;
            font-weight: 600;
        }
    </style>
    @endpush
    @push('scripts')
    <script>
    document.addEventListener('click', function (event) {
        const tab = event.target.closest('.prms-interface-gallery-tab');
        if (!tab) return;
        const galleryId = tab.getAttribute('data-gallery-target');
        const slide = tab.getAttribute('data-slide');
        const gallery = document.getElementById(galleryId);
        if (!gallery) return;
        gallery.querySelectorAll('[data-interface-slide]').forEach((img) => {
            img.classList.toggle('d-none', img.getAttribute('data-interface-slide') !== slide);
        });
        gallery.querySelectorAll('.prms-interface-gallery-tab').forEach((btn) => {
            btn.classList.toggle('is-active', btn === tab);
        });
    });
    </script>
    @endpush
@endonce
