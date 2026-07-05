@if ($paginator->hasPages())
    <nav class="prms-pagination" aria-label="Pagination">
        <ul class="prms-pagination__list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="prms-pagination__nav is-disabled" aria-disabled="true">
                        <span class="prms-pagination__chevron" aria-hidden="true">&lt;</span>
                        <span>Previous</span>
                    </span>
                @else
                    <a class="prms-pagination__nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                        <span class="prms-pagination__chevron" aria-hidden="true">&lt;</span>
                        <span>Previous</span>
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
                                <a class="prms-pagination__page" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="prms-pagination__nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                        <span>Next</span>
                        <span class="prms-pagination__chevron" aria-hidden="true">&gt;</span>
                    </a>
                @else
                    <span class="prms-pagination__nav is-disabled" aria-disabled="true">
                        <span>Next</span>
                        <span class="prms-pagination__chevron" aria-hidden="true">&gt;</span>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
