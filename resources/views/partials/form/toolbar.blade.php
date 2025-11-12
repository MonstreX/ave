<div class="page-header">
    <h1 class="page-title">
        <i class="{{ $resource::getIcon() }}"></i>
        {{ $titleLabel }} {{ $resource::getSingularLabel() }}
    </h1>
    <div class="page-header-actions">
        @php
            $formButtonActions = $formButtonActions ?? [];
            $ajaxFormActions = $ajaxFormActions ?? [];
        @endphp

        @foreach($formButtonActions as $action)
            @if(($action['type'] ?? 'submit') === 'submit')
                <button type="submit"
                        form="ave-resource-form"
                        class="btn btn-{{ $action['variant'] ?? 'primary' }}"
                        name="_ave_form_action"
                        value="{{ $action['intent'] ?? 'save' }}">
                    @if(!empty($action['icon']))
                        <i class="{{ $action['icon'] }}"></i>
                    @endif
                    <span>{{ $action['label'] }}</span>
                </button>
            @elseif(($action['type'] ?? '') === 'link' && !empty($action['url']))
                <a href="{{ $action['url'] }}" class="btn btn-{{ $action['variant'] ?? 'secondary' }}">
                    @if(!empty($action['icon']))
                        <i class="{{ $action['icon'] }}"></i>
                    @endif
                    <span>{{ $action['label'] }}</span>
                </a>
            @endif
        @endforeach

        @foreach($ajaxFormActions as $action)
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
</div>
