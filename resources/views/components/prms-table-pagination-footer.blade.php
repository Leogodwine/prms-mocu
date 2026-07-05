@props([
    'paginator',
])

@php
    use App\Support\PrmsTablePagination;

    $total = method_exists($paginator, 'total') ? (int) $paginator->total() : 0;
    $perPage = method_exists($paginator, 'perPage') ? (int) $paginator->perPage() : PrmsTablePagination::DEFAULT;
    $showFooter = PrmsTablePagination::needsControls($total, $perPage)
        && method_exists($paginator, 'hasPages')
        && $paginator->hasPages();
@endphp

@if ($showFooter)
    <div {{ $attributes->merge(['class' => 'prms-table-pagination-footer d-flex flex-nowrap justify-content-center align-items-center overflow-x-auto w-100 py-2 px-2']) }}>
        {{ method_exists($paginator, 'withQueryString') ? $paginator->withQueryString()->links() : $paginator->links() }}
    </div>
@endif
