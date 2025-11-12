{{-- resources/views/components/tables/badge-column.blade.php --}}
@php
    /** @var \Monstrex\Ave\Core\Columns\BadgeColumn $column */
    $classes = array_filter([
        'table-cell',
        'badge-column',
        'text-' . $column->getAlign(),
        $column->getCellClass(),
    ]);
    $displayValue = is_array($formattedValue) ? implode(', ', $formattedValue) : $formattedValue;
    if($displayValue === null || $displayValue === '') {
        $displayValue = '—';
    }
    $color = $column->resolveColor($displayValue);
    $icon = $column->resolveIcon($displayValue);
@endphp
<td class="{{ implode(' ', $classes) }}">
    <span class="badge badge-{{ $color }} {{ $column->isPill() ? 'badge-pill' : '' }}">
        @if($icon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $column->isUppercase() ? \Illuminate\Support\Str::upper($displayValue) : $displayValue }}
    </span>

    @if($column->getHelpText())
        <div class="table-cell__hint">{{ $column->getHelpText() }}</div>
    @endif
</td>
