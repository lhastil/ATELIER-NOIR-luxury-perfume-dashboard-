{{-- Pagination styled for the admin panel; used as $paginator->links('pagination.admin'). --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        <p class="pagination__summary">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>

        <ul class="pagination__list" role="list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination__link" aria-disabled="true">Previous</span>
                @else
                    <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="pagination__link pagination__link--gap">{{ $element }}</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="pagination__link" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="pagination__link" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="pagination__link" aria-disabled="true">Next</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
