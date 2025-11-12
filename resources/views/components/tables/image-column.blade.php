{{-- resources/views/components/tables/image-column.blade.php --}}
@php
    /** @var \Monstrex\Ave\Core\Columns\ImageColumn $column */
    $classes = array_filter([
        'table-cell',
        'image-column',
        $column->getCellClass(),
    ]);
    $size = $column->getSize();
    $display = $column->getDisplay();
    $images = $column->isMultiple()
        ? (array) $formattedValue
        : array_filter([$formattedValue]);

    if (empty($images) && $column->getFallback()) {
        $images = [$column->getFallback()];
    }

    $boxStyles = [];
    if ($column->getHeight()) {
        $boxStyles[] = 'height: ' . $column->getHeight() . 'px';
        $boxStyles[] = 'width: 100%';
    } else {
        $boxStyles[] = 'width: ' . $size . 'px';
        $boxStyles[] = 'height: ' . $size . 'px';
    }

    $fitClass = $column->getFit() === 'cover'
        ? 'image-column__image--fit-cover'
        : 'image-column__image--fit-height';
@endphp
<td class="{{ implode(' ', $classes) }}">
    <div class="image-column__list image-column__list--{{ $display }}">
        @forelse($images as $index => $img)
            <div class="image-column__item" style="{{ implode('; ', $boxStyles) }}">
                <img
                    src="{{ $img }}"
                    alt="preview"
                    class="image-column__image image-column__image--{{ $column->getShape() }} {{ $fitClass }}"
                    loading="lazy"
                >
            </div>
        @empty
            <span class="text-muted">—</span>
        @endforelse
    </div>

    @if($column->getHelpText())
        <div class="table-cell__hint">{{ $column->getHelpText() }}</div>
    @endif
</td>
