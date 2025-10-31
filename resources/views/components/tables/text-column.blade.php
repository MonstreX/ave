{{-- resources/views/components/tables/text-column.blade.php --}}
<td class="table-cell text-column @if($column->getWidth()) w-{{ $column->getWidth() }} @endif {{ $column->getCellClass() }}">
    {{ $formattedValue }}
    
    @if($column->getHelpText())
        <div class="help-text">{{ $column->getHelpText() }}</div>
    @endif
</td>
