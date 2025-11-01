@extends('ave::layouts.master')

@section('breadcrumbs')
<ol class="ave-navbar__breadcrumb hidden-xs">
    <li class="ave-navbar__breadcrumb-item">
        <a href="{{ (\Illuminate\Support\Facades\Route::has('ave.dashboard') ? route('ave.dashboard') : url('/')) }}" class="ave-navbar__breadcrumb-link">
            <i class="voyager-boat"></i> {{ __('Dashboard') }}
        </a>
    </li>
    <li class="ave-navbar__breadcrumb-item is-active">
        {{ $resource::getLabel() }}
    </li>
</ol>
@endsection

@section('page_header')
<div class="page-header">
    <h1 class="page-title">
        <i class="voyager-data"></i> {{ $resource::getLabel() }}
    </h1>
    <div class="page-header-actions">
        @if((new $resource())->can('create', auth()->user()))
            <a href="{{ route('ave.resource.create', ['slug' => $slug]) }}" class="btn btn-success">
                <i class="voyager-plus"></i> <span>Create {{ $resource::getSingularLabel() }}</span>
            </a>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="page-content">
    {{-- Metrics (Dashboard widgets) --}}
    @if(!empty($metrics))
        <div class="metrics-row">
            @foreach($metrics as $metric)
                <div class="metric-card">
                    @if($metric->getIcon())
                        <span class="metric-icon">{{ $metric->getIcon() }}</span>
                    @else
                        <span class="metric-icon">рџ“Љ</span>
                    @endif
                    <div class="metric-value">{{ $metric->formatValue($metric->getValue()) }}</div>
                    <p class="metric-label">{{ $metric->getLabel() }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Query Tags (Quick filters) --}}
    @if(!empty($queryTags))
        @php
            // Check if any filters are active
            $hasActiveFilters = false;
            foreach ($queryTags as $tag) {
                if ($tag->isActive(request()->query())) {
                    $hasActiveFilters = true;
                    break;
                }
            }
        @endphp
        <div class="query-tags">
            {{-- Reset Filters Button (only visible when filters are active) --}}
            @if($hasActiveFilters)
                <a href="{{ request()->url() }}" class="query-tag-btn query-tag-reset">
                    <i class="voyager-x"></i>
                    <span>Clear All Filters</span>
                </a>
            @endif

            @foreach($queryTags as $tag)
                @php
                    $isActive = $tag->isActive(request()->query());
                    $queryKey = $tag->getQueryKey();
                    $queryValue = $tag->getQueryValue();
                    $variantClass = 'btn-' . ($tag->getVariant() ?? 'primary');
                    $activeClass = $isActive ? 'is-active ' . $variantClass : '';

                    // Clicking toggles the filter on/off
                    if ($isActive) {
                        // Remove this specific filter
                        $href = request()->fullUrlWithoutQuery($queryKey);
                    } else {
                        // Add this filter
                        $href = request()->fullUrlWithQuery([$queryKey => $queryValue]);
                    }
                @endphp
                <a href="{{ $href }}"
                   class="query-tag-btn {{ $activeClass }}"
                   title="{{ $isActive ? 'Click to remove filter' : 'Click to apply filter' }}">
                    @if($tag->getIcon())
                        <span>{{ $tag->getIcon() }}</span>
                    @endif
                    <span>{{ $tag->getLabel() }}</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Bulk Actions Toolbar --}}
    @if(!empty($handlers))
        <div class="bulk-actions-toolbar" id="bulk-actions-toolbar">
            <span class="bulk-selection-count">
                <span id="selected-count">0</span> selected
            </span>
            @foreach($handlers as $handlerClass)
                @php
                    $handler = new $handlerClass();
                @endphp
                <button type="button"
                        class="bulk-action-btn btn-{{ $handler::getVariant() }}"
                        data-handler="{{ class_basename($handlerClass) }}"
                        data-variant="{{ $handler::getVariant() }}"
                        title="{{ $handler::getLabel() }}">
                    @if($handler::getIcon())
                        <i class="{{ $handler::getIcon() }}"></i>
                    @endif
                    {{ $handler::getLabel() }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="panel panel-bordered">
        <div class="panel-body">
            <div class="resource-toolbar">
                <span class="resource-note">
                    Showing {{ $records->total() }} record{{ $records->total() === 1 ? '' : 's' }}
                </span>
                <form method="GET">
                    <div class="resource-search">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="voyager-search"></i>
                            </span>
                            <input
                                type="text"
                                name="q"
                                value="{{ request('q') }}"
                                class="form-control"
                                placeholder="{{ $table->getSearchPlaceholder() ?? 'Search...' }}"
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="voyager-filter"></i> <span>Filter</span>
                    </button>
                </form>
            </div>

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
</div>
@endsection







