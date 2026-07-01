@props([
    'paginator',
])

@php
    use App\Support\PrmsTablePagination;

    $total = method_exists($paginator, 'total') ? (int) $paginator->total() : 0;
    $showFooter = PrmsTablePagination::needsControls($total)
        && method_exists($paginator, 'hasPages')
        && $paginator->hasPages();
@endphp

@if ($showFooter)
    <div {{ $attributes->merge(['class' => 'prms-table-pagination-footer d-flex flex-nowrap justify-content-center overflow-auto w-100']) }}>
        {{ method_exists($paginator, 'withQueryString') ? $paginator->withQueryString()->links() : $paginator->links() }}
    </div>
@endif
