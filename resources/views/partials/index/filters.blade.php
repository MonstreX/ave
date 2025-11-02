@if(!empty($queryTags))
    @php
        // Check if any filters are active
        $hasActiveFilters = false;
        foreach ($queryTags as $tag) {
            if ($tag->isActive(request()->query())) {
                $hasActiveFilters = true;
                break;
            }
        }
    @endphp
    <div class="query-tags">
        {{-- Reset Filters Button (only visible when filters are active) --}}
        @if($hasActiveFilters)
            <a href="{{ request()->url() }}" class="query-tag-btn query-tag-reset">
                <i class="voyager-x"></i>
                <span>Clear All Filters</span>
            </a>
        @endif

        @foreach($queryTags as $tag)
            @php
                $isActive = $tag->isActive(request()->query());
                $queryKey = $tag->getQueryKey();
                $queryValue = $tag->getQueryValue();
                $variantClass = 'btn-' . ($tag->getVariant() ?? 'primary');
                $activeClass = $isActive ? 'is-active ' . $variantClass : '';

                // Clicking toggles the filter on/off
                if ($isActive) {
                    // Remove this specific filter
                    $href = request()->fullUrlWithoutQuery($queryKey);
                } else {
                    // Add this filter
                    $href = request()->fullUrlWithQuery([$queryKey => $queryValue]);
                }
            @endphp
            <a href="{{ $href }}"
               class="query-tag-btn {{ $activeClass }}"
               title="{{ $isActive ? 'Click to remove filter' : 'Click to apply filter' }}">
                @if($tag->getIcon())
                    <span>{{ $tag->getIcon() }}</span>
                @endif
                <span>{{ $tag->getLabel() }}</span>
            </a>
        @endforeach
    </div>
@endif
