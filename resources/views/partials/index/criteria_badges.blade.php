@php
    $criteriaBadges = $criteriaBadges ?? [];
@endphp

@if(!empty($criteriaBadges))
    <div class="criteria-badges">
        @foreach($criteriaBadges as $badge)
            @php
                $variant = $badge['variant'] ?? 'primary';
                $variantClass = $variant === 'limit-warning'
                    ? 'btn-warning criteria-badge--limit'
                    : 'btn-' . $variant;
                $key = $badge['key'] ?? null;
                $removeUrl = $key ? request()->fullUrlWithoutQuery($key) : request()->url();
            @endphp
            <a href="{{ $removeUrl }}" class="criteria-badge {{ $variantClass }}">
                <span>{{ $badge['label'] ?? 'Filter' }}</span>
            </a>
        @endforeach
    </div>
@endif
