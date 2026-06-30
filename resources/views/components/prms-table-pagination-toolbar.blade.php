@props([
    'paginator',
    'noun' => 'rows',
])

@php
    use App\Support\PrmsTablePagination;

    $total = method_exists($paginator, 'total') ? (int) $paginator->total() : 0;
    $showControls = PrmsTablePagination::needsControls($total);
    $showPerPage = $showControls;
@endphp

@if ($showControls)
    <div {{ $attributes->merge(['class' => 'prms-table-pagination-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3 border-bottom']) }}>
        <p class="small text-muted mb-0">
            @if (method_exists($paginator, 'firstItem') && $paginator->firstItem() !== null)
                Showing {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($total) }} {{ $noun }}
            @else
                {{ number_format($total) }} {{ $noun }}
            @endif
        </p>

        @if ($showPerPage)
            <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2 mb-0">
                @foreach (request()->except(['per_page', 'page', $paginator->getPageName()]) as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="prms-per-page-{{ $paginator->getPageName() }}" class="small text-muted mb-0 text-nowrap">Rows</label>
                <select id="prms-per-page-{{ $paginator->getPageName() }}"
                        name="per_page"
                        class="form-select form-select-sm prms-per-page-select"
                        onchange="this.form.submit()">
                    @foreach (PrmsTablePagination::OPTIONS as $option)
                        <option value="{{ $option }}" @selected((int) $paginator->perPage() === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
@endif
