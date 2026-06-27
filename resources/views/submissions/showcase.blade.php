@extends('layouts.app')

@section('title', $submission->title ?: 'System showcase')

@section('breadcrumb')
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item">
        <a href="{{ $backUrl }}">Submissions</a>
    </li>
    <li class="separator"><i class="icon-arrow-right"></i></li>
    <li class="nav-item"><span class="text-muted">{{ \Illuminate\Support\Str::limit($submission->title ?: 'Showcase', 40) }}</span></li>
@endsection

@push('styles')
@include('partials.project-interface-preview-styles')
@endpush

@section('content')
    @php
        $screenshotUrl = $submission->screenshot_path
            ? route('student.submissions.screenshot', $submission)
            : null;
        $demoUrl = trim((string) ($submission->demo_url ?? ''));
        $videoUrl = trim((string) ($submission->video_url ?? ''));
        $videoEmbed = $submission->embeddable_video_url;
        $docUrl = $submission->documentation_path
            ? route('student.submissions.documentation', $submission)
            : null;
        $archiveUrl = route('student.submissions.download', $submission);
        $analysisPending = in_array($submission->showcase_analysis_status, [null, 'pending'], true);
        $overview = $submission->showcase_doc_summary;
        $significance = $submission->showcase_doc_significance
            ?: trim((string) $submission->description)
            ?: 'This repository documents the design, implementation, and operational significance of the submitted system—including architecture decisions, user roles, and how the solution addresses real-world requirements.';
        $readmeBody = $submission->showcase_readme_body;
        $archiveTree = $submission->showcase_archive_tree ?? [];
    @endphp

    <x-prms-greeting-banner
        eyebrow="System showcase"
        :title="$submission->title ?: 'Project showcase'"
        :showHello="false">
        <x-slot:meta>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                <span class="badge bg-secondary">v{{ $submission->version }}</span>
                <span class="badge bg-info-subtle text-info-emphasis">{{ $stageLabel }}</span>
            </div>
        </x-slot:meta>
        <a href="{{ $backUrl }}" class="btn btn-light border rounded-pill px-3">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back
        </a>
        @if ($demoUrl !== '')
            <button type="button" class="btn btn-outline-primary rounded-pill px-3" data-prms-showcase-demo>
                <i class="fas fa-globe me-1" aria-hidden="true"></i> Live demo
            </button>
        @endif
        <a href="{{ $archiveUrl }}" class="btn btn-primary rounded-pill px-3" download="{{ $submission->original_filename }}">
            <i class="fas fa-file-archive me-1" aria-hidden="true"></i> Download source archive
        </a>
    </x-prms-greeting-banner>

    @if ($submission->description)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 prms-eyebrow text-uppercase mb-2">About the system</h2>
                <p class="mb-0 text-strong" style="white-space: pre-line; line-height: 1.55;">{{ $submission->description }}</p>
            </div>
        </div>
    @endif

    @if ($screenshotUrl)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 prms-eyebrow text-uppercase mb-3">Home page interface</h2>
                @include('partials.project-interface-preview', [
                    'submission' => $submission,
                    'size' => 'lg',
                    'onShowcasePage' => true,
                ])
            </div>
        </div>
    @endif

    @if ($demoUrl !== '')
        <div class="card border-0 shadow-sm mb-4 d-none" id="prms-showcase-demo-wrap">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                    <h2 class="h6 prms-eyebrow text-uppercase mb-0">
                        <i class="fas fa-globe me-1 text-primary" aria-hidden="true"></i> Live demo
                    </h2>
                    <a href="{{ $demoUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1" aria-hidden="true"></i> Open in new tab
                    </a>
                </div>
                <div class="ratio ratio-16x9 border rounded-3 overflow-hidden bg-light">
                    <iframe id="prms-showcase-demo-frame"
                            title="Live demo"
                            src="about:blank"
                            data-demo-src="{{ $demoUrl }}"
                            sandbox="allow-forms allow-modals allow-popups allow-pointer-lock allow-same-origin allow-scripts"
                            referrerpolicy="no-referrer"
                            loading="lazy"
                            allowfullscreen></iframe>
                </div>
                <p class="alert alert-info mt-3 mb-0 small" role="note">
                    <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                    Some live sites block being embedded for security. If the frame above is empty, use <strong>Open in new tab</strong>.
                </p>
            </div>
        </div>
    @endif

    @if ($videoUrl !== '')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                    <h2 class="h6 prms-eyebrow text-uppercase mb-0">
                        <i class="fab fa-youtube me-1 text-primary" aria-hidden="true"></i> Video walkthrough
                    </h2>
                    <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1" aria-hidden="true"></i> Open original
                    </a>
                </div>
                @if ($videoEmbed)
                    <div class="ratio ratio-16x9 border rounded-3 overflow-hidden bg-dark">
                        <iframe title="Video walkthrough" src="{{ $videoEmbed }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                loading="lazy"
                                allowfullscreen></iframe>
                    </div>
                @else
                    <p class="alert alert-secondary mb-0 small" role="note">
                        <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                        This video is hosted on a platform we can't embed. Use <strong>Open original</strong>.
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h6 prms-eyebrow text-uppercase mb-0">
                    <i class="far fa-file-alt me-1 text-primary" aria-hidden="true"></i> Documentation
                </h2>
                @if ($docUrl)
                    <a href="{{ $docUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                        <i class="far fa-eye me-1" aria-hidden="true"></i>
                        {{ $submission->documentation_original_filename ?: 'Open documentation' }}
                    </a>
                @endif
            </div>

            <p class="small text-muted mb-2 prms-doc-significance" id="prms-showcase-doc-summary">
                @if ($overview)
                    {{ $overview }}
                @elseif ($analysisPending)
                    Analyzing uploaded documentation…
                @else
                    Summarizing uploaded documentation and system archive…
                @endif
            </p>

            <p class="small fw-semibold text-strong mb-2">Significance</p>
            <p class="small text-muted mb-3 prms-doc-significance" id="prms-showcase-doc-significance">{{ $significance }}</p>

            @include('partials.project-documentation-github-preview', [
                'githubAuthor' => $githubHandle,
                'githubRepo' => $repoSlug,
                'githubAvatar' => strtoupper(substr($githubHandle, 0, 1)),
                'githubCommitMsg' => ($submission->title ?: 'Project update').' · v'.$submission->version,
                'githubCommitSha' => substr($repoSlug, 0, 7),
                'readmeTitle' => $submission->title ?: 'Project README',
                'readmeBody' => $readmeBody,
                'archiveTree' => $archiveTree,
                'analysisPending' => $analysisPending && ! $overview,
            ])
        </div>
    </div>

    <div class="alert alert-light border small mb-0 d-flex align-items-start gap-2" role="note">
        <i class="fas fa-shield-alt text-primary mt-1" aria-hidden="true"></i>
        <span>
            <strong class="text-strong">Secure preview.</strong>
            Demo and video are loaded inside a sandboxed frame. Source archive is private to authorized reviewers.
        </span>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const demoWrap = document.getElementById('prms-showcase-demo-wrap');
    const demoFrame = document.getElementById('prms-showcase-demo-frame');

    function revealLiveDemo() {
        if (!demoWrap) return;
        demoWrap.classList.remove('d-none');
        if (demoFrame && demoFrame.dataset.demoSrc && demoFrame.src === 'about:blank') {
            demoFrame.src = demoFrame.dataset.demoSrc;
        }
        demoWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.querySelectorAll('[data-prms-showcase-demo]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            revealLiveDemo();
        });
    });

    @if ($openDemo ?? false)
        revealLiveDemo();
    @endif
})();
</script>
@endpush

@if ($analysisPending)
    @push('scripts')
    <script>
    (function () {
        const metaUrl = @json(route('student.submissions.showcase-meta', $submission));
        const summaryEl = document.getElementById('prms-showcase-doc-summary');
        const significanceEl = document.getElementById('prms-showcase-doc-significance');
        const readmeBodyEl = document.getElementById('prms-github-readme-body');
        const treeEl = document.getElementById('prms-github-tree');
        let attempts = 0;

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderTree(tree) {
            if (!treeEl) return;
            if (!tree || tree.length === 0) {
                treeEl.innerHTML = '<li class="px-3 py-2 text-muted fst-italic">No archive structure available yet.</li>';
                return;
            }
            treeEl.innerHTML = tree.map(function (item) {
                const isDir = item.type === 'dir';
                const iconClass = isDir ? 'fas fa-folder text-muted' : 'far fa-file-alt text-muted';
                return '<li><i class="' + iconClass + ' me-2" aria-hidden="true"></i>' + escapeHtml(item.name) + '</li>';
            }).join('');
        }

        function poll() {
            fetch(metaUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                .then(function (data) {
                    if (data.overview && summaryEl) summaryEl.textContent = data.overview;
                    if (data.significance && significanceEl) significanceEl.textContent = data.significance;
                    if (data.readme_body && readmeBodyEl) readmeBodyEl.textContent = data.readme_body;
                    if (data.tree) renderTree(data.tree);
                    if (data.status === 'pending' && attempts < 8) {
                        attempts++;
                        window.setTimeout(poll, 3000);
                    }
                })
                .catch(function () {
                    if (attempts < 3) {
                        attempts++;
                        window.setTimeout(poll, 3000);
                    }
                });
        }

        poll();
    })();
    </script>
    @endpush
@endif
