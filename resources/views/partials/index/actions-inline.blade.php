@php
    $globalActions = $globalActions ?? [];
    $bulkActions = $bulkActions ?? [];
@endphp

<div class="resource-actions-inline">
    @if((new $resource())->can('create', auth()->user()))
        <a href="{{ route('ave.resource.create', ['slug' => $slug]) }}" class="btn btn-success">
            <i class="voyager-plus"></i> <span>Create {{ $resource::getSingularLabel() }}</span>
        </a>
    @endif

    @foreach($globalActions as $action)
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
    ])
</div>
