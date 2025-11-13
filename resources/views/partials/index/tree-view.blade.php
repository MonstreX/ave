@php
    $parentCol = $table->getParentColumn() ?? 'parent_id';
    $orderCol = $table->getOrderColumn() ?? 'order';
    $labelCol = $table->getTreeLabelColumn();
    $maxDepth = $table->getTreeMaxDepth();
@endphp

<div class="panel panel-bordered">
    <div class="panel-body">
        {{-- Toolbar --}}
        <div class="resource-controls-row">
            <div class="resource-controls-left">
                <button type="button" class="btn btn-secondary tree-expand-all">
                    <i class="voyager-angle-down"></i> Expand All
                </button>
                <button type="button" class="btn btn-secondary tree-collapse-all">
                    <i class="voyager-angle-up"></i> Collapse All
                </button>

                <span class="resource-note ml-3">
                    Total: {{ $records->count() }} items
                </span>
            </div>
            <div class="resource-controls-right">
                @if($table->isSearchable())
                    @include('ave::partials.index.search', ['table' => $table])
                @endif
            </div>
        </div>

        {{-- Criteria Badges --}}
        @include('ave::partials.index.criteria_badges', [
            'criteriaBadges' => $criteriaBadges ?? [],
            'slug' => $slug,
        ])

        {{-- Tree Container --}}
        <div class="tree-container dd"
             id="tree-container-{{ $slug }}"
             data-tree="true"
             data-parent-column="{{ $parentCol }}"
             data-order-column="{{ $orderCol }}"
             data-max-depth="{{ $maxDepth }}"
             data-slug="{{ $slug }}"
             data-update-endpoint="{{ route('ave.resource.update-tree', ['slug' => $slug]) }}">
            <ol class="tree-list dd-list">
                @foreach($records as $item)
                    @if($item->{$parentCol} === null)
                        @include('ave::partials.index.tree-item', [
                            'item' => $item,
                            'allRecords' => $records,
                            'table' => $table,
                            'slug' => $slug,
                            'resource' => $resource,
                            'resourceInstance' => $resourceInstance,
                            'currentUser' => auth()->user(),
                            'rowActions' => $rowActions ?? [],
                            'parentCol' => $parentCol,
                            'orderCol' => $orderCol,
                            'labelCol' => $labelCol,
                            'level' => 0
                        ])
                    @endif
                @endforeach
            </ol>
        </div>
    </div>
</div>
