<div class="row">
    @foreach($columns as $column)
        {!! $column->render($context) !!}
    @endforeach
</div>
