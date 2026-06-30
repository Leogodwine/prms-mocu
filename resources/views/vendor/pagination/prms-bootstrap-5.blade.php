@if ($paginator->hasPages())
    <nav class="prms-pagination" aria-label="Pagination">
        <ul class="prms-pagination__list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="prms-pagination__nav is-disabled" aria-disabled="true">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">Previous</span>
                    </span>
                @else
                    <a class="prms-pagination__nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">Previous</span>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="prms-pagination__ellipsis" aria-hidden="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="prms-pagination__page is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="prms-pagination__page" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="prms-pagination__nav" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <span class="d-none d-sm-inline">Next</span>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="prms-pagination__nav is-disabled" aria-disabled="true">
                        <span class="d-none d-sm-inline">Next</span>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
