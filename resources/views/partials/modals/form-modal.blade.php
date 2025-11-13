@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('ave.resource.update', ['slug' => $slug, 'id' => $model->getKey()])
        : route('ave.resource.store', ['slug' => $slug]);
@endphp

<form id="ave-modal-form" action="{{ $action }}" method="POST" enctype="multipart/form-data"
      @if($isEdit) data-model-type="{{ get_class($model) }}" data-model-id="{{ $model->getKey() }}" @endif>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @foreach($formLayout as $entry)
        @php
            $entryType = $entry['type'] ?? (isset($entry['columns']) ? 'row-legacy' : null);
        @endphp

        @if($entryType === 'row')
            {!! $entry['component']->render($context) !!}
        @elseif($entryType === 'component')
            {!! $entry['component']->render($context) !!}
        @elseif($entryType === 'row-legacy')
            <div class="form-row">
                @foreach($entry['columns'] as $column)
                    <div class="form-column" style="grid-column: span {{ $column['span'] }}">
                        @foreach($column['fields'] as $field)
                            {!! $field->render($context) !!}
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
</form>
