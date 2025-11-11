@php
    $rowActions = $rowActions ?? [];
    $bulkActions = $bulkActions ?? [];
    $hasBulkSelection = !empty($bulkActions);
@endphp

<div class="panel panel-bordered">
    <div class="panel-body">
        <div class="resource-table">
            <table class="table">
                <thead>
                <tr>
                    @if($hasBulkSelection)
                        <th class="checkbox-column">
                            <input type="checkbox" class="select-all-checkbox" id="select-all" />
                        </th>
                    @endif
                    @foreach($table->getColumns() as $column)
                        <th>
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
                                <input type="checkbox" class="row-selector" value="{{ $item->getKey() }}" />
                            </td>
                        @endif
                        @foreach($table->getColumns() as $column)
                            <td>
                                {{ $column->formatValue($item->{$column->key()}, $item) }}
                            </td>
                        @endforeach
                        <td class="text-right">
                            <div class="table-actions">
                                @if(!empty($rowActions))
                                    <div class="btn-group table-row-actions">
                                        @foreach($rowActions as $action)
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
                                                class="btn btn-sm btn-{{ $variant }}"
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
                                                @if($actionIcon)
                                                    <i class="{{ $actionIcon }}"></i>
                                                @endif
                                                <span>{{ $actionLabel }}</span>
                                            </button>
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

        <div class="resource-pagination">
            {{ $records->links() }}
        </div>
    </div>
</div>
