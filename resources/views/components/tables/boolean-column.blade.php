{{-- resources/views/components/tables/boolean-column.blade.php --}}
@php
    /** @var \Monstrex\Ave\Core\Columns\BooleanColumn $column */
    $isActive = $column->isActive($value);
    $classes = array_filter([
        'table-cell',
        'boolean-column',
        'text-' . $column->getAlign(),
        $column->getCellClass(),
    ]);
    $valueStyles = $column->hasCustomStyles() ? $column->getCellStyle() : '';
@endphp
<td class="{{ implode(' ', $classes) }}">
    @php
        $onColor = '#16a34a';
        $offColor = '#d1d5db';
        $dotColor = $isActive ? $onColor : $offColor;
    @endphp

    @if($column->isToggleEnabled())
        <button
            type="button"
            class="ave-inline-toggle ave-inline-toggle--dot {{ $isActive ? 'is-on' : 'is-off' }}"
            data-ave-inline-toggle
            data-endpoint="{{ route('ave.resource.inline-update', ['slug' => $slug, 'id' => $record->getKey()]) }}"
            data-field="{{ $column->inlineField() }}"
            data-true-value="{{ (string) $column->getTrueValue() }}"
            data-false-value="{{ (string) $column->getFalseValue() }}"
            data-current-value="{{ $isActive ? (string) $column->getTrueValue() : (string) $column->getFalseValue() }}"
            data-on-color="{{ $onColor }}"
            data-off-color="{{ $offColor }}"
            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
        >
            <span class="toggle-indicator" style="display:none;"></span>
            @php
                $iconClass = $isActive ? $column->getTrueIcon() : $column->getFalseIcon();
            @endphp
            <span class="toggle-icon" style="display:none;">
                <i class="{{ $iconClass }}"></i>
            </span>
            <span class="toggle-label" style="display:none;">
                {{ $isActive ? $column->getTrueLabel() : $column->getFalseLabel() }}
            </span>
            <span
                class="toggle-dot"
                style="background-color:{{ $dotColor }};"
            ></span>
        </button>
    @else
        <span
            class="status-dot"
            style="background-color:{{ $dotColor }};"
        ></span>
    @endif

    @if($column->getHelpText())
        <div class="table-cell__hint">{{ $column->getHelpText() }}</div>
    @endif
</td>
