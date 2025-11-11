@php
    $criteriaBadges = $criteriaBadges ?? [];
    $hasQueryTags = !empty($queryTags);
    $hasCriteriaBadges = !empty($criteriaBadges);
    $hasBadges = $hasCriteriaBadges || $hasQueryTags;
@endphp

@if($hasBadges)
    <div class="query-tags">
        @if($hasCriteriaBadges)
            @foreach($criteriaBadges as $badge)
                @php
                    $variantClass = 'btn-' . ($badge['variant'] ?? 'primary');
                    $key = $badge['key'] ?? null;
                    $removeUrl = $key ? request()->fullUrlWithoutQuery($key) : request()->url();
                @endphp
                <a href="{{ $removeUrl }}" class="query-tag-btn {{ $variantClass }}">
                    <span>{{ $badge['label'] ?? 'Filter' }}</span>
                </a>
            @endforeach
        @endif

        @if($hasQueryTags)
            @php
                $hasActiveFilters = false;
                foreach ($queryTags as $tag) {
                    if ($tag->isActive(request()->query())) {
                        $hasActiveFilters = true;
                        break;
                    }
                }
            @endphp

            @if($hasActiveFilters && !$hasCriteriaBadges)
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

                    if ($isActive) {
                        $href = request()->fullUrlWithoutQuery($queryKey);
                    } else {
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
        @endif
    </div>
@endif
