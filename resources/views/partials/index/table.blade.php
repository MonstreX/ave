@php
    $rowActions = $rowActions ?? [];
    $bulkActions = $bulkActions ?? [];
    $globalActions = $globalActions ?? [];
    $criteriaBadges = $criteriaBadges ?? [];
    $hasBulkSelection = !empty($bulkActions);
    $tableFilters = method_exists($table, 'getFilters') ? $table->getFilters() : [];
    $hasFilters = !empty($tableFilters);
    $recordsTotal = $records->total();
    $resourceInstance = $resourceInstance ?? new $resource();
    $currentUser = auth()->user();
@endphp

<div class="panel panel-bordered">
    <div class="panel-body">
        <div class="resource-controls-row">
            <div class="resource-controls-left">
                @if($hasFilters)
                    @include('ave::partials.index.filters', [
                        'table' => $table,
                        'records' => $records,
                        'slug' => $slug,
                    ])
                @else
                    <div class="resource-note">
                        Showing {{ $recordsTotal }} record{{ $recordsTotal === 1 ? '' : 's' }}
                    </div>
                @endif
            </div>
            <div class="resource-controls-right">
                @include('ave::partials.index.search', ['table' => $table])
            </div>
        </div>

        @include('ave::partials.index.criteria_badges', [
            'criteriaBadges' => $criteriaBadges,
            'slug' => $slug,
        ])

        <div class="resource-table">
            <table class="table table-striped table-hover table-bordered">
                <thead>
                <tr>
                    @if($hasBulkSelection)
                        <th class="checkbox-column">
                            <label class="checkbox-label checkbox-label--compact">
                                <input type="checkbox" class="checkbox-input select-all-checkbox" id="select-all" />
                                <span class="checkbox-custom checkbox-custom--sm"></span>
                            </label>
                        </th>
                    @endif
                    @foreach($table->getColumns() as $column)
                        @php
                            $headerClasses = trim('text-' . $column->getAlign() . ' ' . $column->getHeaderClass());
                            $headerStyles = [];
                            $width = $column->getWidth();
                            $minWidth = $column->getMinWidth();
                            $maxWidth = $column->getMaxWidth();

                            if ($width !== null) {
                                $headerStyles[] = 'width: ' . (is_numeric($width) ? $width . 'px' : $width);
                            }
                            if ($minWidth) {
                                $headerStyles[] = 'min-width: ' . $minWidth;
                            }
                            if ($maxWidth) {
                                $headerStyles[] = 'max-width: ' . $maxWidth;
                            }
                        @endphp
                        <th class="{{ $headerClasses }}" @if(!empty($headerStyles)) style="{{ implode('; ', $headerStyles) }}" @endif>
                            @if($column->isSortable())
                                @php
                                    $direction = request('dir', 'asc') === 'asc' ? 'desc' : 'asc';
                                @endphp
                                <a href="?sort={{ $column->key() }}&dir={{ $direction }}" class="ave-link">
                                    {{ $column->getLabel() }}
                                    @if(request('sort') === $column->key())
                                        <i class="voyager-angle-{{ request('dir', 'asc') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            @else
                                {{ $column->getLabel() }}
                            @endif
                        </th>
                    @endforeach
                    <th class="text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $item)
                    <tr class="resource-row" data-id="{{ $item->getKey() }}">
                        @if($hasBulkSelection)
                            <td class="checkbox-column">
                                @php
                                    $rowCheckboxId = 'row-select-' . $item->getKey();
                                @endphp
                                <label class="checkbox-label checkbox-label--compact" for="{{ $rowCheckboxId }}">
                                    <input
                                        type="checkbox"
                                        id="{{ $rowCheckboxId }}"
                                        class="checkbox-input row-selector"
                                        value="{{ $item->getKey() }}"
                                    />
                                    <span class="checkbox-custom checkbox-custom--sm"></span>
                                </label>
                            </td>
                        @endif
                        @foreach($table->getColumns() as $column)
                            @php
                                $rawValue = $column->resolveRecordValue($item);
                                $formattedValue = $column->formatValue($rawValue, $item);
                                $columnView = $column->resolveView();
                            @endphp
                            @php
                                $link = $column->hasLink()
                                    ? $column->resolveLink($item, $resource)
                                    : null;
                            @endphp
                            @include($columnView, [
                                'column' => $column,
                                'record' => $item,
                                'value' => $rawValue,
                                'formattedValue' => $formattedValue,
                                'slug' => $slug,
                                'link' => $link,
                                'resourceClass' => $resource,
                            ])
                        @endforeach
                        <td class="text-right">
                            <div class="table-actions">
                                @if(!empty($rowActions))
                                    <div class="table-action-buttons">
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
                                               class="table-action-icon {{ $isDeleteLike ? 'delete' : '' }}"
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($table->getColumns()) + 2 }}" class="text-center text-muted">
                            No {{ strtolower($resource::getLabel()) }} found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="resource-table-footer">
            <div class="resource-note">
                Showing {{ $recordsTotal }} record{{ $recordsTotal === 1 ? '' : 's' }}
            </div>
        </div>

        <div class="resource-pagination">
            {{ $records->links() }}
        </div>
    </div>
</div>
