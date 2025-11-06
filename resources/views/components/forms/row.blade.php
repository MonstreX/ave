<div class="row">
    @foreach($columns as $column)
        <div class="col-{{ $column['span'] }}">
            @foreach($column['fields'] as $field)
                {!! $field->render($context) !!}
            @endforeach
        </div>
    @endforeach
</div>

