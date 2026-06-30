<form method="POST"
      action="{{ route('public.research.index') }}"
      class="public-research-mobile-refine d-lg-none"
      id="publicResearchMobileRefine"
      role="search">
    @csrf
    <input type="hidden" name="_filter_action" value="apply">
    @include('partials.public-research-filter-fields', [
        'filters' => $filters,
        'except' => ['search'],
    ])

    <label for="public-mobile-search" class="visually-hidden">Search publications</label>
    <div class="input-group input-group-sm public-research-mobile-refine__search prms-search-input-group">
        <span class="input-group-text bg-white">
            <i class="fas fa-search text-muted" aria-hidden="true"></i>
        </span>
        <input id="public-mobile-search"
               type="search"
               name="search"
               class="form-control"
               list="public-mobile-search-hints"
               placeholder="Search title, author, department, keywords…"
               value="{{ $filters['search'] }}"
               autocomplete="off">
        <datalist id="public-mobile-search-hints">
            @foreach ($departments as $department)
                <option value="{{ $department->department_name }}"></option>
            @endforeach
            @foreach ($authors as $authorName)
                <option value="{{ $authorName }}"></option>
            @endforeach
        </datalist>
    </div>

    <div class="public-research-mobile-refine__toolbar">
        <div class="dropdown public-research-mobile-refine__dropdown">
            <button class="btn btn-sm dropdown-toggle w-100 public-research-mobile-refine__toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    id="publicMobileFilterMenu">
                <i class="fas fa-sliders-h me-1 text-primary" aria-hidden="true"></i>
                Filters
            </button>
            <div class="dropdown-menu dropdown-menu-end public-research-mobile-refine__menu" aria-labelledby="publicMobileFilterMenu">
                @include('partials.public-research-refine-filters', [
                    'filters' => $filters,
                    'departments' => $departments,
                    'authors' => $authors,
                    'filterResetUrl' => $filterResetUrl,
                    'fieldPrefix' => 'public-mobile-',
                    'authorListId' => 'public-author-options-mobile',
                    'hideDepartmentAuthorFilters' => true,
                    'plainLinks' => true,
                ])
            </div>
        </div>
    </div>
</form>
