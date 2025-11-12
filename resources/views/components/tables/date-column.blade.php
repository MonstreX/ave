{{-- resources/views/components/tables/date-column.blade.php --}}
@php
    $classes = array_filter([
        'table-cell',
        'date-column',
        'text-' . $column->getAlign(),
        $column->getCellClass(),
    ]);

    $styles = [];
    $width = $column->getWidth();
    if ($width !== null) {
        $styles[] = 'width: ' . (is_numeric($width) ? $width . 'px' : $width);
    }
@endphp
<td class="{{ implode(' ', $classes) }}" @if(!empty($styles)) style="{{ implode('; ', $styles) }}" @endif>
    @if($formattedValue !== '' && $formattedValue !== null)
        {{ $formattedValue }}
    @else
        <span class="empty">—</span>
    @endif

    @if($column->getHelpText())
        <div class="table-cell__hint">{{ $column->getHelpText() }}</div>
    @endif
</td>
