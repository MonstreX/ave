<div class="panel panel-bordered">
    <div class="panel-body">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data"
            @if($isEdit)
                data-model-type="{{ get_class($model) }}"
                data-model-id="{{ $model->getKey() }}"
            @endif
        >
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            @foreach($formLayout as $row)
                <div class="form-row">
                    @foreach($row['columns'] as $column)
                        <div class="form-column" style="grid-column: span {{ $column['span'] }}">
                            @foreach($column['fields'] as $field)
                                {!! $field->render($context) !!}
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach

            @include('ave::partials.form.actions')
        </form>
    </div>
</div>
