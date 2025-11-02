@if(!empty($handlers))
    <div class="bulk-actions-toolbar" id="bulk-actions-toolbar">
        <span class="bulk-selection-count">
            <span id="selected-count">0</span> selected
        </span>
        @foreach($handlers as $handlerClass)
            @php
                $handler = new $handlerClass();
            @endphp
            <button type="button"
                    class="bulk-action-btn btn-{{ $handler::getVariant() }}"
                    data-handler="{{ class_basename($handlerClass) }}"
                    data-variant="{{ $handler::getVariant() }}"
                    title="{{ $handler::getLabel() }}">
                @if($handler::getIcon())
                    <i class="{{ $handler::getIcon() }}"></i>
                @endif
                {{ $handler::getLabel() }}
            </button>
        @endforeach
    </div>
@endif
