@php
    $fieldPrefix = $fieldPrefix ?? 'filter-';
    $authorListId = $authorListId ?? 'public-author-options';
    $activeSinceYear = $filters['since_year'] ?? '';
    $activeDepartment = (int) ($filters['department_id'] ?? 0);
    $activeAuthor = $filters['author'] ?? '';
    $activeSort = $filters['sort'] ?? 'recent';
    $showClearLink = $showClearLink ?? true;
    $hideDepartmentAuthorFilters = $hideDepartmentAuthorFilters ?? false;
@endphp

<div class="public-refine-filters">
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
                        <label for="{{ $fieldPrefix }}year-from" class="visually-hidden">From year</label>
                        <input id="{{ $fieldPrefix }}year-from" type="number" name="year_from" min="1900" max="2100"
                               class="form-control form-control-sm" placeholder="From"
                               value="{{ $filters['year_from'] ?: '' }}">
                    </div>
                    <div class="col-6">
                        <label for="{{ $fieldPrefix }}year-to" class="visually-hidden">To year</label>
                        <input id="{{ $fieldPrefix }}year-to" type="number" name="year_to" min="1900" max="2100"
                               class="form-control form-control-sm" placeholder="To"
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

    @unless ($hideDepartmentAuthorFilters)
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
            <label for="{{ $fieldPrefix }}author-search" class="visually-hidden">Filter by author</label>
            <div class="input-group input-group-sm">
                <input id="{{ $fieldPrefix }}author-search"
                       type="text"
                       name="author"
                       class="form-control"
                       list="{{ $authorListId }}"
                       placeholder="Author name"
                       value="{{ $filters['author'] }}">
                <datalist id="{{ $authorListId }}">
                    @foreach ($authors as $authorName)
                        <option value="{{ $authorName }}"></option>
                    @endforeach
                </datalist>
            </div>
        </form>
    </div>
    @endunless

    @if ($showClearLink)
        <a href="{{ $filterResetUrl }}" class="small text-muted">Clear all filters</a>
    @endif
</div>
