{{-- resources/views/components/tables/date-column.blade.php --}}
<td class="table-cell date-column @if($column->getWidth()) w-{{ $column->getWidth() }} @endif {{ $column->getCellClass() }}">
    @if($formattedValue !== '')
        {{ $formattedValue }}
    @else
        <span class="empty">-</span>
    @endif
    
    @if($column->getHelpText())
        <div class="help-text">{{ $column->getHelpText() }}</div>
    @endif
</td>
