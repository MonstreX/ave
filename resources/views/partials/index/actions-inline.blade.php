@php
    $globalActions = $globalActions ?? [];
    $bulkActions = $bulkActions ?? [];
    $resourceInstance = $resourceInstance ?? new $resource();
    $currentUser = auth()->user();
    $hasGlobalActions = count($globalActions) > 0;
@endphp

<div class="resource-actions-inline">
    @if(!$hasGlobalActions && $resourceInstance->can('create', $currentUser))
        <a href="{{ route('ave.resource.create', array_merge(['slug' => $slug], request()->query())) }}" class="btn btn-success">
            <i class="voyager-plus"></i> <span>{{ __('ave::actions.create') }} {{ $resource::getSingularLabel() }}</span>
        </a>
    @endif

    @foreach($globalActions as $action)
        @php
            $actionAbility = $action->ability() ?? 'viewAny';
        @endphp
        @if(!$resourceInstance->can($actionAbility, $currentUser))
            @continue
        @endif
        @php
            $actionContext = $currentUser
                ? \Monstrex\Ave\Core\Actions\Support\ActionContext::global($resource, $currentUser)
                : null;
        @endphp
        @if($actionContext && !$action->authorize($actionContext))
            @continue
        @endif
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
        <button
            type="button"
            class="btn btn-{{ $variant }}"
            data-ave-action="global"
            data-ave-action-key="{{ $action->key() }}"
            data-variant="{{ $variant }}"
            data-action-endpoint="{{ route('ave.resource.action.global', ['slug' => $slug, 'action' => $action->key()]) }}"
            data-action-method="POST"
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

    @include('ave::partials.index.bulk_actions', [
        'bulkActions' => $bulkActions,
        'slug' => $slug,
        'resource' => $resource,
        'resourceInstance' => $resourceInstance,
    ])
</div>
