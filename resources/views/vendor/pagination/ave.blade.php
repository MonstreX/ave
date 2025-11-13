@if ($paginator->hasPages())
    @php
        $from = ($paginator->currentPage() - 1) * $paginator->perPage() + 1;
        $to = min($from + $paginator->perPage() - 1, $paginator->total());
        $total = $paginator->total();
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
    @endphp

    <nav class="ave-pagination-wrapper" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-controls">
            {{-- First Page Button --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-first disabled" aria-disabled="true" aria-label="First Page">
                    <i class="voyager-angle-double-left"></i>
                </span>
            @else
                <a href="{{ $paginator->url(1) }}" class="pagination-btn pagination-first" rel="first" aria-label="First Page">
                    <i class="voyager-angle-double-left"></i>
                </a>
            @endif

            {{-- Previous Page Button --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-prev disabled" aria-disabled="true" aria-label="Previous">
                    <i class="voyager-angle-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn pagination-prev" rel="prev" aria-label="Previous">
                    <i class="voyager-angle-left"></i>
                </a>
            @endif

            {{-- Page Numbers --}}
            <ul class="pagination">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $currentPage)
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </ul>

            {{-- Next Page Button --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn pagination-next" rel="next" aria-label="Next">
                    <i class="voyager-angle-right"></i>
                </a>
            @else
                <span class="pagination-btn pagination-next disabled" aria-disabled="true" aria-label="Next">
                    <i class="voyager-angle-right"></i>
                </span>
            @endif

            {{-- Last Page Button --}}
            @if ($currentPage == $lastPage)
                <span class="pagination-btn pagination-last disabled" aria-disabled="true" aria-label="Last Page">
                    <i class="voyager-angle-double-right"></i>
                </span>
            @else
                <a href="{{ $paginator->url($lastPage) }}" class="pagination-btn pagination-last" rel="last" aria-label="Last Page">
                    <i class="voyager-angle-double-right"></i>
                </a>
            @endif

            {{-- Jump to Page --}}
            <div class="pagination-jump">
                <label for="pagination-jump-input" class="pagination-jump-label">Go to:</label>
                <input
                    type="number"
                    id="pagination-jump-input"
                    class="pagination-jump-input"
                    min="1"
                    max="{{ $lastPage }}"
                    value="{{ $currentPage }}"
                    data-last-page="{{ $lastPage }}"
                    data-base-url="{{ $paginator->url(1) }}"
                    aria-label="Go to page"
                />
            </div>
        </div>
    </nav>
@endif
