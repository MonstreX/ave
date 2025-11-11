@php
    $globalActions = $globalActions ?? [];
@endphp

<div class="page-header">
    <h1 class="page-title">
        <i class="{{ $resource::getIcon() }}"></i> {{ $resource::getLabel() }}
    </h1>
    <div class="page-header-actions">
        @if(!empty($globalActions))
            <div class="btn-group global-actions">
                @foreach($globalActions as $action)
                    @php
                        $actionConfig = method_exists($action, 'toArray') ? $action->toArray() : [];
                        $actionLabel = $actionConfig['label'] ?? ucfirst($action->key());
                        $actionIcon = $actionConfig['icon'] ?? null;
                        $actionColor = $actionConfig['color'] ?? 'default';
                        $requiresConfirmation = $actionConfig['requiresConfirmation'] ?? false;
                        $confirmMessage = $actionConfig['confirmMessage'] ?? null;
                    @endphp
                    @php
                        $actionData = [
                            'label' => $actionLabel,
                            'form' => $action->form(),
                        ];
                    @endphp
                    <button
                        type="button"
                        class="btn btn-{{ $actionColor }}"
                        data-ave-action="global"
                        data-ave-action-key="{{ $action->key() }}"
                        data-variant="{{ $actionColor }}"
                        data-action-endpoint="{{ route('ave.resource.action.global', ['slug' => $slug, 'action' => $action->key()]) }}"
                        data-action-method="POST"
                        data-action-config='@json($actionData)'
                        @if($requiresConfirmation)
                            data-action-confirm="true"
                            @if($confirmMessage)
                                data-action-confirm-message="{{ $confirmMessage }}"
                            @endif
                        @endif
                    >
                        @if($actionIcon)
                            <i class="{{ $actionIcon }}"></i>
                        @endif
                        <span>{{ $actionLabel }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        @if((new $resource())->can('create', auth()->user()))
            <a href="{{ route('ave.resource.create', ['slug' => $slug]) }}" class="btn btn-success">
                <i class="voyager-plus"></i> <span>Create {{ $resource::getSingularLabel() }}</span>
            </a>
        @endif

        @include('ave::partials.index.bulk_actions', [
            'bulkActions' => $bulkActions ?? [],
            'slug' => $slug,
        ])
    </div>
</div>
