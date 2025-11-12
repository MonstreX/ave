{{-- resources/views/components/tables/template-column.blade.php --}}
@php
    /** @var \Monstrex\Ave\Core\Columns\TemplateColumn $column */
    $classes = array_filter([
        'table-cell',
        'template-column',
        $column->getCellClass(),
    ]);
    $template = $column->getTemplateView();
    $data = $column->resolveTemplateData($record);
@endphp
<td class="{{ implode(' ', $classes) }}">
    @if($template)
        @include($template, array_merge($data, [
            'value' => $value,
            'formattedValue' => $formattedValue,
            'record' => $record,
            'column' => $column,
        ]))
    @else
        @if($column->shouldEscape())
            {{ $formattedValue }}
        @else
            {!! $formattedValue !!}
        @endif
    @endif

    @if($column->getHelpText())
        <div class="table-cell__hint">{{ $column->getHelpText() }}</div>
    @endif
</td>

