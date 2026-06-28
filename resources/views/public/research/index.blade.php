@extends('layouts.public')

@section('hide_page_header', true)

@section('title', 'Institutional Repository')

@section('content')

<section class="position-relative public-research-page" style="padding: 4rem 0 3rem;">
    <div class="container public-research-container">
        {{-- ────────── Hero / search ────────── --}}
        <div class="row align-items-center g-4 g-lg-5 mb-4 public-research-hero">
            <div class="col-lg-5 col-md-6 public-research-hero__media">
                <div class="public-research-hero__material" aria-hidden="true">
                    <div class="public-research-hero__material-surface public-research-hero__material-surface--back"></div>
                    <div class="public-research-hero__material-surface public-research-hero__material-surface--front">
                        <span class="material-symbols-outlined public-research-hero__material-icon public-research-hero__material-icon--main">local_library</span>
                    </div>
                    <div class="public-research-hero__material-chip public-research-hero__material-chip--science">
                        <span class="material-symbols-outlined">science</span>
                        <span>Research</span>
                    </div>
                    <div class="public-research-hero__material-chip public-research-hero__material-chip--article">
                        <span class="material-symbols-outlined">article</span>
                        <span>Reports</span>
                    </div>
                    <div class="public-research-hero__material-chip public-research-hero__material-chip--code">
                        <span class="material-symbols-outlined">code</span>
                        <span>Projects</span>
                    </div>
                    <div class="public-research-hero__material-badge">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 col-md-6 public-research-hero__content">
                <h1 class="display-5 fw-bold public-research-hero__title mt-0 mb-3">
                    Institutional repository
                </h1>
                <p class="lead mb-0 public-research-hero__lead">
                    Discover approved proposals, research reports, and computer-based course projects authored by MoCU students. Every record is reviewed before publication.
                </p>

                <form action="{{ route('public.research.index') }}" method="POST" class="mt-4 public-research-search" role="search">
                    @csrf
                    <input type="hidden" name="_filter_action" value="apply">
                    <label for="prms-search" class="visually-hidden">Search the repository</label>
                    <div class="input-group input-group-lg public-research-search__group">
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
                    <div class="d-flex flex-wrap align-items-center gap-1 public-research-meta__filters">
                        @foreach ([
                            '' => 'All',
                            'proposal' => 'Proposals',
                            'research' => 'Reports',
                            'project' => 'Projects',
                        ] as $value => $label)
                            @if (! $loop->first)
                                <span class="public-research-meta__sep text-muted" aria-hidden="true">·</span>
                            @endif
                            <form method="POST" action="{{ route('public.research.index') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="_filter_action" value="apply">
                                @include('partials.public-research-filter-fields', ['filters' => $filters, 'override' => ['type' => $value]])
                                <button type="submit"
                                        class="public-research-category-link {{ $activeCategory === $value ? 'is-active' : '' }}">
                                    {{ $label }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ────────── Results + filters ────────── --}}
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

                            <div class="mt-2 public-refine-filters">
                                @php
                                    $activeSinceYear = $filters['since_year'] ?? '';
                                    $activeDepartment = (int) ($filters['department_id'] ?? 0);
                                    $activeAuthor = $filters['author'] ?? '';
                                    $activeSort = $filters['sort'] ?? 'recent';
                                @endphp

                                <div class="public-refine-block">
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

                                <div class="public-refine-block">
                                    <ul class="list-unstyled mb-0 public-quick-filter-list">
                                        @foreach ([
                                            'relevance' => 'Relevance',
                                            'recent' => 'Date',
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

                                <div class="public-refine-block">
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

                                <div class="public-refine-block public-refine-block--author">
                                    <ul class="list-unstyled mb-1 public-quick-filter-list public-quick-filter-scroll">
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
                                    <form method="POST" action="{{ route('public.research.index') }}" class="public-refine-author-search">
                                        @csrf
                                        <input type="hidden" name="_filter_action" value="apply">
                                        @include('partials.public-research-filter-fields', ['filters' => $filters, 'except' => ['author']])
                                        <label for="filter-author-search" class="visually-hidden">Filter by author</label>
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
            <div class="col-lg-9" id="prms-research-results" aria-live="polite">
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

                @if (trim((string) ($filters['search'] ?? '')) !== '' && ! empty($relatedSearches))
                    <aside class="card border-0 shadow-sm public-research-related mt-4" aria-label="Related searches">
                        <div class="card-body public-research-panel-body">
                            <h2 class="h6 fw-bold text-strong mb-3">Related searches</h2>
                            <ul class="list-unstyled mb-0 public-related-search-list">
                                @foreach ($relatedSearches as $term)
                                    <li>
                                        <form method="POST" action="{{ route('public.research.index') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="_filter_action" value="apply">
                                            @include('partials.public-research-filter-fields', [
                                                'filters' => $filters,
                                                'override' => ['search' => $term],
                                            ])
                                            <button type="submit" class="btn btn-sm btn-light border rounded-pill public-related-search-link">
                                                {{ $term }}
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </aside>
                @endif
                </div>

                @include('partials.public-research-loading-skeleton')
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
<style>
    .material-symbols-outlined {
        font-family: "Material Symbols Outlined", sans-serif;
        font-weight: normal;
        font-style: normal;
        font-size: 1.25rem;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
        -webkit-font-smoothing: antialiased;
    }

    body.prms-public-page,
    body.prms-kaiadmin-public .wrapper .main-panel.prms-public-main {
        background-color: #ffffff !important;
    }

    .public-research-page {
        background-color: #ffffff;
    }

    .public-research-container {
        --public-panel-padding: 1.5rem;
        --public-section-gap: 1rem;
    }

    .public-research-hero__material {
        position: relative;
        width: 100%;
        max-width: 22rem;
        margin-inline: auto;
        aspect-ratio: 1;
        min-height: 16rem;
    }

    .public-research-hero__material-surface {
        position: absolute;
        border-radius: 1.75rem;
    }

    .public-research-hero__material-surface--back {
        inset: 8% 6% 14% 14%;
        background: linear-gradient(145deg, #e8f1fd 0%, #dce9fb 100%);
        transform: rotate(-6deg);
    }

    .public-research-hero__material-surface--front {
        inset: 12% 10% 10% 10%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(160deg, #ffffff 0%, #f5f9ff 100%);
        box-shadow:
            0 1px 2px rgba(21, 114, 232, 0.08),
            0 8px 24px rgba(21, 114, 232, 0.12),
            0 24px 48px rgba(15, 23, 42, 0.06);
    }

    .public-research-hero__material-icon--main {
        font-size: clamp(4.5rem, 14vw, 6.5rem);
        color: var(--prms-primary, #1572E8);
        font-variation-settings: "FILL" 1, "wght" 400, "GRAD" 0, "opsz" 48;
    }

    .public-research-hero__material-chip {
        position: absolute;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        background: #ffffff;
        color: var(--prms-text, #334155);
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.06),
            0 4px 12px rgba(15, 23, 42, 0.08);
    }

    .public-research-hero__material-chip .material-symbols-outlined {
        font-size: 1.125rem;
    }

    .public-research-hero__material-chip--science {
        top: 6%;
        right: 0;
        color: #0d47a1;
    }

    .public-research-hero__material-chip--science .material-symbols-outlined {
        color: #1565c0;
    }

    .public-research-hero__material-chip--article {
        bottom: 18%;
        left: -2%;
        color: #1b5e20;
    }

    .public-research-hero__material-chip--article .material-symbols-outlined {
        color: #2e7d32;
    }

    .public-research-hero__material-chip--code {
        bottom: 4%;
        right: 8%;
        color: #4a148c;
    }

    .public-research-hero__material-chip--code .material-symbols-outlined {
        color: #6a1b9a;
    }

    .public-research-hero__material-badge {
        position: absolute;
        top: 22%;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 50%;
        background: #e3f2fd;
        color: var(--prms-primary, #1572E8);
        box-shadow: 0 4px 12px rgba(21, 114, 232, 0.2);
    }

    .public-research-hero__material-badge .material-symbols-outlined {
        font-size: 1.5rem;
        font-variation-settings: "FILL" 1, "wght" 400, "GRAD" 0, "opsz" 24;
    }

    .public-research-hero__content {
        text-align: left;
    }

    .public-research-hero__lead {
        color: #000000;
        font-weight: 400;
    }

    .public-research-hero__title {
        color: var(--prms-primary, #1572E8);
        letter-spacing: -0.025em;
    }

    .public-research-search {
        max-width: 100%;
    }

    .public-research-search__group {
        box-shadow: var(--prms-shadow);
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

    .public-research-category-link {
        border: 0;
        background: none;
        padding: 0;
        font-size: 0.875rem;
        line-height: 1.4;
        color: var(--prms-text-muted, #64748b);
        text-decoration: none;
        cursor: pointer;
    }

    .public-research-category-link:hover,
    .public-research-category-link:focus {
        color: var(--prms-primary, #1572E8);
        text-decoration: underline;
    }

    .public-research-category-link.is-active {
        color: var(--prms-primary, #1572E8);
        font-weight: 600;
        text-decoration: none;
    }

    .public-research-meta__sep {
        font-size: 0.875rem;
        line-height: 1;
        user-select: none;
    }

    .public-research-panel-body {
        padding: var(--public-panel-padding) !important;
    }

    .public-research-refine .public-research-panel-body {
        --public-panel-padding: 1.15rem;
    }

    .public-refine-filters {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .public-refine-block {
        margin-bottom: 0 !important;
    }

    .public-refine-block--author .public-refine-author-search {
        margin-top: 0.25rem;
    }

    .public-research-refine .mb-4,
    .public-research-refine .mb-3 {
        margin-bottom: 0.5rem !important;
    }

    .public-quick-filter-list li + li {
        margin-top: 0;
    }

    .public-quick-filter-list .public-quick-filter-btn {
        color: var(--prms-text, #334155);
        text-decoration: none;
        width: 100%;
        padding-left: 0 !important;
        padding-right: 0 !important;
        line-height: 1.35;
        min-height: 0;
        padding-top: 0.1rem !important;
        padding-bottom: 0.1rem !important;
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
        color: var(--prms-text, #334155);
        text-decoration: none;
        line-height: 1.35;
        font-weight: 500;
    }

    .public-related-search-link:hover {
        color: var(--prms-primary, #1572E8);
        background: var(--prms-primary-soft, rgba(21, 114, 232, 0.08)) !important;
        border-color: var(--prms-primary, #1572E8) !important;
    }

    .public-related-search-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .public-related-search-list li {
        margin: 0;
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

    @media (max-width: 767.98px) {
        .public-research-hero__content {
            text-align: center;
        }

        .public-research-meta__filters {
            justify-content: center;
            margin-left: 0;
        }

        .public-research-meta__count {
            width: 100%;
            justify-content: center;
        }

        .public-research-hero__material {
            max-width: 18rem;
            min-height: 14rem;
            margin-bottom: 0.5rem;
        }

        .public-research-hero__material-chip {
            font-size: 0.6875rem;
            padding: 0.35rem 0.6rem;
        }

        .public-research-hero__material-chip--article {
            left: 0;
        }
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
