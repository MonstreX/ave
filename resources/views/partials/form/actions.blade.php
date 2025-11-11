<div class="form-actions">
    <a href="{{ $cancelUrl }}" class="btn btn-secondary">
        {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel }}
    </button>
    @php
        $formActions = $formActions ?? [];
    @endphp
    @foreach($formActions as $action)
        @php
            $actionLabel = $action->label();
            $actionIcon = $action->icon();
            $variant = $action->color();
            $confirm = $action->confirm();
            $actionConfig = [
                'label' => $actionLabel,
                'form' => $action->form(),
            ];
        @endphp
        <button type="button"
                class="btn btn-{{ $variant }}"
                data-ave-action="form"
                data-ave-action-key="{{ $action->key() }}"
                data-variant="{{ $variant }}"
                data-action-endpoint="{{ route('ave.resource.action.form', ['slug' => $slug, 'id' => $isEdit ? $model->getKey() : null, 'action' => $action->key()]) }}"
                data-action-method="POST"
                data-action-form-selector="#ave-resource-form"
                data-action-config='@json($actionConfig)'
                @if($confirm)
                    data-action-confirm="true"
                    data-action-confirm-message="{{ $confirm }}"
                @endif
        >
            @if($actionIcon)
                <i class="{{ $actionIcon }}"></i>
            @endif
            <span>{{ $actionLabel }}</span>
        </button>
    @endforeach
</div>
