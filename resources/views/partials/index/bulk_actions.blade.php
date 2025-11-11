@php
    $bulkActions = $bulkActions ?? [];
@endphp

@if(!empty($bulkActions))
    <div class="bulk-actions-inline" id="bulk-actions-toolbar" aria-hidden="true">
        <span class="bulk-selection-count">
            <span id="selected-count">0</span> {{ __('Selected') }}
        </span>
        @foreach($bulkActions as $action)
            @php
                $actionLabel = $action->label();
                $actionIcon = $action->icon();
                $variant = $action->color();
                $requiresConfirmation = $action->confirm();
                $actionConfig = [
                    'label' => $actionLabel,
                    'form' => $action->form(),
                ];
            @endphp
            <button type="button"
                    class="btn btn-{{ $variant }} bulk-action-btn"
                    data-ave-action="bulk"
                    data-ave-action-key="{{ $action->key() }}"
                    data-variant="{{ $variant }}"
                    data-action-endpoint="{{ route('ave.resource.action.bulk', ['slug' => $slug, 'action' => $action->key()]) }}"
                    data-action-method="POST"
                    data-action-config='@json($actionConfig)'
                    @if($requiresConfirmation)
                        data-action-confirm="true"
                        data-action-confirm-message="{{ $action->confirm() }}"
                    @endif
                    title="{{ $actionLabel }}">
                @if($actionIcon)
                    <i class="{{ $actionIcon }}"></i>
                @endif
                {{ $actionLabel }}
            </button>
        @endforeach
    </div>
@endif
