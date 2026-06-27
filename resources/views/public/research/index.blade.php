@extends('layouts.public')

@section('hide_page_header', true)

@section('title', 'Institutional Repository')

@section('content')

<section class="position-relative public-research-page" style="background: var(--prms-page-bg); padding: 4rem 0 3rem;">
    <div class="container public-research-container">
        {{-- ────────── Hero / search ────────── --}}
        <div class="card border-0 shadow-sm public-research-hero-card mb-4">
            <div class="card-body public-research-panel-body text-center public-research-hero">
                <h1 class="display-5 fw-bold text-strong mt-2 mb-3" style="letter-spacing: -0.025em;">
                    Project and Research repository
                </h1>
                <p class="lead text-muted mb-0">
                    Discover approved <strong>proposals, research reports</strong>, and
                    <strong>computer-based course projects</strong> authored
                    by MoCU students. Every record is reviewed before publication.
                </p>

                <form action="{{ route('public.research.index') }}" method="POST" class="mt-4 mx-auto public-research-search" role="search">
                    @csrf
                    <input type="hidden" name="_filter_action" value="apply">
                    <label for="prms-search" class="visually-hidden">Search the repository</label>
                    <div class="input-group input-group-lg" style="box-shadow: var(--prms-shadow);">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted" aria-hidden="true"></i>
                        </span>
                        <input
                            id="prms-search"
                            type="text"
                            name="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Search by title, keywords, or abstract…"
                            value="{{ $filters['search'] }}">
                    </div>

                    @include('partials.public-research-filter-fields', ['filters' => $filters, 'except' => ['search']])
                </form>

                @php
                    $activeCategory = $filters['type'] ?? '';
                    $visibleTotal = $activeCategory === ''
                        ? ($categoryCounts['all'] ?? $projects->total())
                        : ($categoryCounts[$activeCategory] ?? $projects->total());
                @endphp
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3 public-research-meta">
                    <p class="small text-muted mb-0 public-research-meta__count">
                        {{ $visibleTotal }} approved publication{{ $visibleTotal === 1 ? '' : 's' }} available
                    </p>
                    <div class="d-flex flex-wrap gap-2 public-research-meta__filters">
                        @foreach ([
                            '' => 'All',
                            'proposal' => 'Proposals',
                            'research' => 'Reports',
                            'project' => 'Projects',
                        ] as $value => $label)
                            <form method="POST" action="{{ route('public.research.index') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="_filter_action" value="apply">
                                @include('partials.public-research-filter-fields', ['filters' => $filters, 'override' => ['type' => $value]])
                                <button type="submit"
                                        class="btn btn-sm rounded-pill {{ $activeCategory === $value ? 'btn-primary' : 'btn-light border' }}">
                                    {{ $label }}
                                    <span class="ms-1 opacity-75">({{ $categoryCounts[$value === '' ? 'all' : $value] ?? 0 }})</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ────────── Results + filters ────────── --}}
        @php
            $resultsColumnClass = ! empty($relatedSearches) ? 'col-lg-6' : 'col-lg-9';
        @endphp
        <div class="row public-research-results g-4">

            {{-- ── Refine sidebar (left) ── --}}
            <div class="col-lg-3">
                <aside class="card border-0 shadow-sm public-research-refine h-100" aria-label="Filter results">
                    <div class="card-body public-research-panel-body">
                        <details class="public-refine-details" open>
                            <summary class="h6 fw-bold text-strong d-flex align-items-center mb-0 public-refine-summary">
                                <i class="fas fa-sliders-h text-primary me-2" aria-hidden="true"></i>
                                Refine results
                            </summary>

                            <div class="mt-3">
                                @php
                                    $activeSinceYear = $filters['since_year'] ?? '';
                                    $activeDepartment = (int) ($filters['department_id'] ?? 0);
                                    $activeAuthor = $filters['author'] ?? '';
                                    $activeSort = $filters['sort'] ?? 'recent';
                                @endphp

                                <div class="mb-4">
                                    <h3 class="small fw-semibold text-muted text-uppercase mb-2">Time</h3>
                                    <ul class="list-unstyled mb-0 public-quick-filter-list">
                                        @foreach ([
                                            '' => 'Any time',
                                            '2026' => 'Since 2026',
                                            '2025' => 'Since 2025',
                                            '2022' => 'Since 2022',
                                            'custom' => 'Custom range…',
                                        ] as $value => $label)
                                            <li>
                                                <form method="POST" action="{{ route('public.research.index') }}">
                                                    @csrf
                                                    <input type="hidden" name="_filter_action" value="apply">
                                                    @include('partials.public-research-filter-fields', [
                                                        'filters' => $filters,
                                                        'override' => [
                                                            'since_year' => $value,
                                                            'year_from' => $value === 'custom' ? ($filters['year_from'] ?? '') : '',
                                                            'year_to' => $value === 'custom' ? ($filters['year_to'] ?? '') : '',
                                                        ],
                                                    ])
                                                    <button type="submit"
                                                            class="btn btn-link btn-sm text-start px-0 py-1 public-quick-filter-btn {{ $activeSinceYear === $value ? 'active' : '' }}">
                                                        {{ $label }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($activeSinceYear === 'custom')
                                        <form method="POST" action="{{ route('public.research.index') }}" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="_filter_action" value="apply">
                                            @include('partials.public-research-filter-fields', [
                                                'filters' => $filters,
                                                'override' => ['since_year' => 'custom'],
                                                'except' => ['year_from', 'year_to'],
                                            ])
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label for="filter-year-from" class="form-label small text-muted mb-1">From</label>
                                                    <input id="filter-year-from" type="number" name="year_from" min="1900" max="2100"
                                                           class="form-control form-control-sm" placeholder="Year"
                                                           value="{{ $filters['year_from'] ?: '' }}">
                                                </div>
                                                <div class="col-6">
                                                    <label for="filter-year-to" class="form-label small text-muted mb-1">To</label>
                                                    <input id="filter-year-to" type="number" name="year_to" min="1900" max="2100"
                                                           class="form-control form-control-sm" placeholder="Year"
                                                           value="{{ $filters['year_to'] ?: '' }}">
                                                </div>
                                            </div>
                                        </form>
                                    @endif
                                </div>

                                <div class="mb-4">
                                    <h3 class="small fw-semibold text-muted text-uppercase mb-2">Sort by</h3>
                                    <ul class="list-unstyled mb-0 public-quick-filter-list">
                                        @foreach ([
                                            'relevance' => 'Sort by relevance',
                                            'recent' => 'Sort by date',
                                        ] as $value => $label)
                                            <li>
                                                <form method="POST" action="{{ route('public.research.index') }}">
                                                    @csrf
                                                    <input type="hidden" name="_filter_action" value="apply">
                                                    @include('partials.public-research-filter-fields', [
                                                        'filters' => $filters,
                                                        'override' => ['sort' => $value],
                                                    ])
                                                    <button type="submit"
                                                            class="btn btn-link btn-sm text-start px-0 py-1 public-quick-filter-btn {{ $activeSort === $value ? 'active' : '' }}">
                                                        {{ $label }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="mb-4">
                                    <h3 class="small fw-semibold text-muted text-uppercase mb-2">Department</h3>
                                    <ul class="list-unstyled mb-0 public-quick-filter-list public-quick-filter-scroll">
                                        <li>
                                            <form method="POST" action="{{ route('public.research.index') }}">
                                                @csrf
                                                <input type="hidden" name="_filter_action" value="apply">
                                                @include('partials.public-research-filter-fields', [
                                                    'filters' => $filters,
                                                    'override' => ['department_id' => ''],
                                                ])
                                                <button type="submit"
                                                        class="btn btn-link btn-sm text-start px-0 py-1 public-quick-filter-btn {{ $activeDepartment === 0 ? 'active' : '' }}">
                                                    All departments
                                                </button>
                                            </form>
                                        </li>
                                        @foreach ($departments as $department)
                                            <li>
                                                <form method="POST" action="{{ route('public.research.index') }}">
                                                    @csrf
                                                    <input type="hidden" name="_filter_action" value="apply">
                                                    @include('partials.public-research-filter-fields', [
                                                        'filters' => $filters,
                                                        'override' => ['department_id' => $department->id],
                                                    ])
                                                    <button type="submit"
                                                            class="btn btn-link btn-sm text-start px-0 py-1 public-quick-filter-btn {{ $activeDepartment === (int) $department->id ? 'active' : '' }}">
                                                        {{ $department->department_name }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="mb-3">
                                    <h3 class="small fw-semibold text-muted text-uppercase mb-2">Author</h3>
                                    <ul class="list-unstyled mb-2 public-quick-filter-list public-quick-filter-scroll">
                                        <li>
                                            <form method="POST" action="{{ route('public.research.index') }}">
                                                @csrf
                                                <input type="hidden" name="_filter_action" value="apply">
                                                @include('partials.public-research-filter-fields', [
                                                    'filters' => $filters,
                                                    'override' => ['author' => ''],
                                                ])
                                                <button type="submit"
                                                        class="btn btn-link btn-sm text-start px-0 py-1 public-quick-filter-btn {{ $activeAuthor === '' ? 'active' : '' }}">
                                                    All authors
                                                </button>
                                            </form>
                                        </li>
                                        @foreach ($authors as $authorName)
                                            <li>
                                                <form method="POST" action="{{ route('public.research.index') }}">
                                                    @csrf
                                                    <input type="hidden" name="_filter_action" value="apply">
                                                    @include('partials.public-research-filter-fields', [
                                                        'filters' => $filters,
                                                        'override' => ['author' => $authorName],
                                                    ])
                                                    <button type="submit"
                                                            class="btn btn-link btn-sm text-start px-0 py-1 public-quick-filter-btn {{ $activeAuthor === $authorName ? 'active' : '' }}">
                                                        {{ $authorName }}
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <form method="POST" action="{{ route('public.research.index') }}">
                                        @csrf
                                        <input type="hidden" name="_filter_action" value="apply">
                                        @include('partials.public-research-filter-fields', ['filters' => $filters, 'except' => ['author']])
                                        <label for="filter-author-search" class="form-label small text-muted mb-1">Search author</label>
                                        <div class="input-group input-group-sm">
                                            <input id="filter-author-search"
                                                   type="text"
                                                   name="author"
                                                   class="form-control"
                                                   list="public-author-options"
                                                   placeholder="Author name"
                                                   value="{{ $filters['author'] }}">
                                            <datalist id="public-author-options">
                                                @foreach ($authors as $authorName)
                                                    <option value="{{ $authorName }}"></option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </form>
                                </div>

                                <a href="{{ $filterResetUrl }}" class="small text-muted">Clear all filters</a>
                            </div>
                        </details>
                    </div>
                </aside>
            </div>

            {{-- ── Results (center) ── --}}
            <div class="{{ $resultsColumnClass }}" id="prms-research-results" aria-live="polite">
                <div id="prms-research-results-content">
                @if ($projects->isEmpty())
                    <div class="card border-0 shadow-sm public-research-empty h-100">
                        <div class="card-body public-research-panel-body text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-surface-soft rounded-circle mb-3"
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-search text-muted" aria-hidden="true" style="font-size: 1.8rem;"></i>
                            </div>
                            <h3 class="h5 fw-bold text-strong">No publications found</h3>
                            <p class="text-muted">Try adjusting your filters or search terms.</p>
                            <a href="{{ route('public.research.index') }}" class="btn btn-outline-primary">Clear filters</a>
                        </div>
                    </div>
                @else
                    <div class="d-flex flex-column public-research-cards gap-3">
                        @foreach ($projects as $project)
                            @if (in_array(strtolower((string) ($project->project_type ?? '')), ['proposal', 'research'], true))
                                @include('partials.public-scholar-result', ['project' => $project])
                            @else
                                @include('partials.public-publication-card', ['project' => $project])
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-5 d-flex justify-content-center">
                        {{ $projects->appends(request()->query())->links() }}
                    </div>
                @endif
                </div>

                @include('partials.public-research-loading-skeleton')
            </div>

            {{-- ── Related searches (right) ── --}}
            @if (! empty($relatedSearches))
                <div class="col-lg-3">
                    <aside class="card border-0 shadow-sm public-research-related h-100" aria-label="Related searches">
                        <div class="card-body public-research-panel-body">
                            <h2 class="h6 fw-bold text-strong mb-3">Related searches</h2>
                            <ul class="list-unstyled mb-0 public-related-search-list">
                                @foreach ($relatedSearches as $term)
                                    <li class="mb-2">
                                        <form method="POST" action="{{ route('public.research.index') }}">
                                            @csrf
                                            <input type="hidden" name="_filter_action" value="apply">
                                            @include('partials.public-research-filter-fields', [
                                                'filters' => $filters,
                                                'override' => ['search' => $term],
                                            ])
                                            <button type="submit" class="btn btn-link btn-sm text-start px-0 py-0 public-related-search-link">
                                                {{ $term }}
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </div>
</section>

@endsection

@push('styles')
<style>
    .public-research-container {
        --public-panel-padding: 1.5rem;
        --public-section-gap: 1rem;
    }

    .public-research-search {
        max-width: 640px;
    }

    .public-research-meta,
    .public-research-hero,
    .public-research-results {
        width: 100%;
    }

    .public-research-meta {
        align-items: center !important;
    }

    .public-research-meta__count {
        display: flex;
        align-items: center;
        min-height: 2rem;
        line-height: 1.4;
    }

    .public-research-meta__filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-left: auto;
    }

    .public-research-meta__filters form {
        display: inline-flex;
        align-items: center;
        margin: 0;
    }

    .public-research-meta__filters .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .public-research-panel-body {
        padding: var(--public-panel-padding) !important;
    }

    .public-research-refine .mb-4,
    .public-research-refine .mb-3 {
        margin-bottom: var(--public-section-gap) !important;
    }

    .public-research-refine h3.mb-2 {
        margin-bottom: 0.5rem !important;
    }

    .public-research-results {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    .public-research-empty .public-research-panel-body {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
        padding-inline-end: 0.75rem !important;
    }

    .public-research-cards {
        gap: 1rem !important;
    }

    .public-publication-card .card-body {
        padding: var(--public-panel-padding) !important;
    }

    .public-publication-card {
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .public-publication-card:hover {
        box-shadow: var(--prms-shadow-md, 0 8px 24px rgba(15, 23, 42, 0.08)) !important;
    }

    .publication-title-link:hover {
        color: var(--prms-primary, #1572E8) !important;
    }

    .publication-type-badge {
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .publication-meta {
        font-size: 0.82rem;
    }

    .publication-authors {
        line-height: 1.5;
        letter-spacing: 0.01em;
    }

    .publication-abstract {
        max-width: 72ch;
        line-height: 1.55;
    }

    .publication-preview-link:hover img {
        opacity: 0.92;
    }

    .public-quick-filter-list .public-quick-filter-btn {
        color: var(--prms-text, #334155);
        text-decoration: none;
        width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .public-quick-filter-list .public-quick-filter-btn:hover,
    .public-quick-filter-list .public-quick-filter-btn:focus {
        color: var(--prms-primary, #1572E8);
        text-decoration: none;
    }

    .public-quick-filter-list .public-quick-filter-btn.active {
        color: var(--prms-primary, #1572E8);
        font-weight: 600;
    }

    .public-quick-filter-scroll {
        max-height: 220px;
        overflow-y: auto;
    }

    .public-refine-summary {
        cursor: pointer;
        list-style: none;
    }

    .public-refine-details summary::-webkit-details-marker {
        display: none;
    }

    .public-related-search-link {
        color: var(--prms-primary, #1572E8);
        text-decoration: none;
        line-height: 1.35;
    }

    .public-related-search-link:hover {
        text-decoration: underline;
    }

    .public-related-search-list {
        max-height: 220px;
        overflow-y: auto;
    }

    /* Scholar-style result rows (proposals & reports only) */
    .gs-result {
        background: var(--prms-surface);
        border: 1px solid var(--prms-border, #e5e7eb);
        border-radius: 0.5rem;
        padding: 1rem 1.15rem;
        margin-bottom: 0;
    }

    .gs-result-format {
        font-size: 0.78rem;
        color: #006621;
        margin-bottom: 0.15rem;
        font-weight: 600;
    }

    .gs-result-title {
        font-size: 1.05rem;
        line-height: 1.35;
        margin: 0 0 0.2rem;
        font-weight: 600;
    }

    .gs-result-title a {
        color: #1a0dab;
        text-decoration: none;
    }

    .gs-result-title a:hover {
        text-decoration: underline;
    }

    .gs-result-source {
        font-size: 0.82rem;
        color: #006621;
        margin: 0 0 0.35rem;
    }

    .gs-result-snippet {
        font-size: 0.84rem;
        line-height: 1.55;
        color: #4d5156;
        margin: 0 0 0.35rem;
    }

    .gs-result-actions {
        font-size: 0.78rem;
    }

    .gs-result-actions a {
        color: #1a0dab;
        text-decoration: none;
    }

    .gs-result-actions a:hover {
        text-decoration: underline;
    }

    .gs-action-sep {
        color: #70757a;
        margin: 0 0.2rem;
    }

    .gs-muted-action {
        color: #70757a;
    }

    .prms-research-loading {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .prms-skeleton-card {
        background: var(--prms-surface);
        border: 1px solid var(--prms-border, #e5e7eb);
        border-radius: 0.5rem;
        padding: 1.15rem;
    }

    .prms-skeleton-line {
        height: 0.85rem;
        border-radius: 999px;
        background: linear-gradient(90deg, #eef2f7 0%, #f8fafc 50%, #eef2f7 100%);
        background-size: 200% 100%;
        animation: prms-skeleton-pulse 1.2s ease-in-out infinite;
        margin-bottom: 0.65rem;
    }

    .prms-skeleton-line--title { width: 72%; height: 1.1rem; }
    .prms-skeleton-line--meta { width: 42%; }
    .prms-skeleton-line--short { width: 55%; margin-bottom: 0; }

    @keyframes prms-skeleton-pulse {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        .prms-skeleton-line { animation: none; background: #eef2f7; }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var page = document.querySelector('.public-research-page');
        if (!page) return;

        var results = document.getElementById('prms-research-results');
        var content = document.getElementById('prms-research-results-content');
        var loading = document.getElementById('prms-research-loading');

        page.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (!results || !content || !loading) return;
                results.setAttribute('aria-busy', 'true');
                content.classList.add('d-none');
                loading.classList.remove('d-none');
            });
        });
    })();
</script>
@endpush
