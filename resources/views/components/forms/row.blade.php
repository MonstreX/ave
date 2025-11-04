<div class="form-row">
    @foreach($columns as $column)
        <div class="form-column" style="grid-column: span {{ $column['span'] }}">
            @foreach($column['fields'] as $field)
                {!! $field->render($context) !!}
            @endforeach
        </div>
    @endforeach
</div>

