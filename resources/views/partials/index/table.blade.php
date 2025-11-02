<div class="panel panel-bordered">
    <div class="panel-body">
        @include('ave::partials.index.search')

        <div class="resource-table">
            <table class="table">
                <thead>
                <tr>
                    @if($table->hasBulkActions())
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
                        @if($table->hasBulkActions())
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
                                @if((new $resource())->can('update', auth()->user(), $item))
                                    <a href="{{ route('ave.resource.edit', ['slug' => $slug, 'id' => $item->getKey()]) }}" class="btn btn-sm btn-primary">
                                        <i class="voyager-edit"></i> Edit
                                    </a>
                                @endif
                                @if((new $resource())->can('delete', auth()->user(), $item))
                                    <form action="{{ route('ave.resource.destroy', ['slug' => $slug, 'id' => $item->getKey()]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">
                                            <i class="voyager-trash"></i> Delete
                                        </button>
                                    </form>
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
