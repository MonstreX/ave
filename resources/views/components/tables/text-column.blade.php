{{-- resources/views/components/tables/text-column.blade.php --}}
@php
    $classes = array_filter([
        'table-cell',
        'text-column',
        'text-' . $column->getAlign(),
        $column->shouldWrap() ? 'text-wrap' : 'text-nowrap',
        $column->getCellClass(),
    ]);

    $styles = [];
    $width = $column->getWidth();
    $minWidth = $column->getMinWidth();
    $maxWidth = $column->getMaxWidth();

    if ($width !== null) {
        $styles[] = 'width: ' . (is_numeric($width) ? $width . 'px' : $width);
    }
    if ($minWidth) {
        $styles[] = 'min-width: ' . $minWidth;
    }
    if ($maxWidth) {
        $styles[] = 'max-width: ' . $maxWidth;
    }

    $displayValue = $formattedValue === null || $formattedValue === '' ? '—' : $formattedValue;
    
    // Custom column styles (font-size, font-weight, color, etc.)
    $valueStyles = $column->hasCustomStyles() ? $column->getCellStyle() : '';
@endphp
<td class="{{ implode(' ', $classes) }}" @if(!empty($styles)) style="{{ implode('; ', $styles) }}" @endif>
    <div class="table-cell__value" @if($column->getTooltip()) title="{{ $column->getTooltip() }}" @endif @if($valueStyles) style="{{ $valueStyles }}" @endif>
        @if(!empty($link))
            <a href="{{ $link }}" class="table-link">
                @if($column->shouldEscape())
                    {{ $displayValue }}
                @else
                    {!! $displayValue !!}
                @endif
            </a>
        @else
            @if($column->shouldEscape())
                {{ $displayValue }}
            @else
                {!! $displayValue !!}
            @endif
        @endif
    </div>

    @if($column->getHelpText())
        <div class="table-cell__hint">{{ $column->getHelpText() }}</div>
    @endif
</td>
