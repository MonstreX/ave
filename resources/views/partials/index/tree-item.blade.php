@php
    // Find children of this item
    $children = $allRecords->filter(fn($r) => $r->{$parentCol} == $item->getKey());
    $hasChildren = $children->count() > 0;

    // Determine label to display
    $displayLabel = $labelCol ? $item->{$labelCol} : $item->getKey();
@endphp

<li class="tree-item dd-item" data-id="{{ $item->getKey() }}">
    <div class="dd-handle">
        {{-- Drag handle --}}
        <button type="button" class="tree-drag-handle" aria-label="Drag to reorder">
            <i class="voyager-move"></i>
        </button>

        {{-- Content --}}
        <div class="dd-content dd-nodrag">
            <div class="tree-item-info">
                <span class="tree-item-label">{{ $displayLabel }}</span>

                {{-- Display column values --}}
                @foreach($table->getColumns() as $column)
                    @if($column->key() !== $labelCol)
                        @php
                            $rawValue = $column->resolveRecordValue($item);
                            $formattedValue = $column->formatValue($rawValue, $item);
                        @endphp
                        <span class="tree-meta">
                            @if($formattedValue)
                                {!! $formattedValue !!}
                            @endif
                        </span>
                    @endif
                @endforeach
            </div>

            {{-- Expand/Collapse buttons --}}
            @if($hasChildren)
                <div class="dd-item-btns">
                    <button type="button" data-action="expand" class="hidden" aria-label="Expand">
                        <i class="voyager-angle-down"></i>
                    </button>
                    <button type="button" data-action="collapse" aria-label="Collapse">
                        <i class="voyager-angle-up"></i>
                    </button>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="tree-actions dd-nodrag">
            @if(!empty($rowActions))
                <div class="tree-action-buttons">
                    @foreach($rowActions as $action)
                        @php
                            $actionAbility = $action->ability() ?? 'update';
                            $canRun = $resourceInstance->can($actionAbility, $currentUser, $item);
                            $actionContext = ($currentUser && $canRun)
                                ? \Monstrex\Ave\Core\Actions\Support\ActionContext::row($resource, $currentUser, $item)
                                : null;
                        @endphp
                        @if(!$canRun || ($actionContext && !$action->authorize($actionContext)))
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
                            $fallbackIcon = match ($action->key()) {
                                'edit' => 'voyager-edit',
                                'delete' => 'voyager-trash',
                                'view', 'show' => 'voyager-eye',
                                default => 'voyager-dot-001',
                            };
                            $iconClass = $actionIcon ?: $fallbackIcon;
                            $isDeleteLike = $action->key() === 'delete' || $variant === 'danger';
                        @endphp
                        <a href="javascript:void(0)"
                           class="tree-action-icon {{ $isDeleteLike ? 'delete' : '' }}"
                           title="{{ $actionLabel }}"
                           data-ave-action="row"
                           data-ave-action-key="{{ $action->key() }}"
                           data-variant="{{ $variant }}"
                           data-action-endpoint="{{ route('ave.resource.action.row', ['slug' => $slug, 'id' => $item->getKey(), 'action' => $action->key()]) }}"
                           data-action-method="POST"
                           data-action-config='@json($actionConfig)'
                           @if($confirm)
                               data-action-confirm="true"
                               data-action-confirm-message="{{ $confirm }}"
                           @endif
                        >
                            <i class="{{ $iconClass }}"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Recursive children --}}
    @if($hasChildren)
        <ol class="tree-list dd-list">
            @foreach($children as $child)
                @include('ave::partials.index.tree-item', [
                    'item' => $child,
                    'allRecords' => $allRecords,
                    'table' => $table,
                    'slug' => $slug,
                    'resource' => $resource,
                    'resourceInstance' => $resourceInstance,
                    'currentUser' => $currentUser,
                    'rowActions' => $rowActions,
                    'parentCol' => $parentCol,
                    'orderCol' => $orderCol,
                    'labelCol' => $labelCol,
                    'level' => $level + 1
                ])
            @endforeach
        </ol>
    @endif
</li>
