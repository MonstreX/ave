@php
    $filters = isset($table) && method_exists($table, 'getFilters') ? $table->getFilters() : [];
    $hasSearch = isset($table) && method_exists($table, 'isSearchable') ? $table->isSearchable() : false;
    $hasFilters = $hasSearch || !empty($filters);
@endphp

@if($hasFilters)
    <div class="panel panel-bordered resource-filters">
        <div class="panel-body">
            <form method="GET" class="filters-form">
                <div class="resource-toolbar">
                    <span class="resource-note">
                        Showing {{ $records->total() }} record{{ $records->total() === 1 ? '' : 's' }}
                    </span>
                    <div class="filters-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="voyager-filter"></i> <span>Apply Filters</span>
                        </button>
                        <a href="{{ route('ave.resource.index', ['slug' => $slug]) }}" class="btn btn-default">
                            <i class="voyager-refresh"></i> <span>Reset</span>
                        </a>
                    </div>
                </div>

                <div class="row filters-grid">
                    @if($hasSearch)
                        <div class="col-md-4 col-sm-6">
                            @include('ave::partials.index.search', [
                                'table' => $table,
                                'fieldId' => 'ave-filter-search',
                            ])
                        </div>
                    @endif

                    @foreach($filters as $filter)
                        @php
                            $filterKey = $filter->key();
                            $filterValue = request()->input($filterKey);
                            $filterData = method_exists($filter, 'toArray') ? $filter->toArray() : [];
                            $filterLabel = $filterData['label'] ?? ucfirst(str_replace('_', ' ', $filterKey));
                            $columnClass = $filter instanceof \Monstrex\Ave\Core\Filters\DateFilter ? 'col-md-4' : 'col-md-3';
                        @endphp
                        <div class="{{ $columnClass }} col-sm-6 form-group filters-field" data-filter="{{ $filterKey }}">
                            <label class="control-label" for="filter-{{ $filterKey }}">{{ $filterLabel }}</label>

                            @if($filter instanceof \Monstrex\Ave\Core\Filters\SelectFilter)
                                @php
                                    $options = $filterData['options'] ?? [];
                                    $isMultiple = $filterData['multiple'] ?? false;
                                    $fieldName = $isMultiple ? "{$filterKey}[]" : $filterKey;
                                    $selectedValues = $isMultiple
                                        ? (array) ($filterValue ?? [])
                                        : ($filterValue ?? '');
                                @endphp
                                <select
                                    id="filter-{{ $filterKey }}"
                                    name="{{ $fieldName }}"
                                    class="form-control"
                                    @if($isMultiple) multiple @endif
                                >
                                    @if(!$isMultiple)
                                        <option value="">Select option</option>
                                    @endif
                                    @foreach($options as $optionValue => $optionLabel)
                                        <option
                                            value="{{ $optionValue }}"
                                            @if($isMultiple && in_array($optionValue, $selectedValues))
                                                selected
                                            @elseif(!$isMultiple && (string) $optionValue === (string) $selectedValues)
                                                selected
                                            @endif
                                        >
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($filter instanceof \Monstrex\Ave\Core\Filters\DateFilter)
                                @php
                                    $fromValue = data_get($filterValue, 'from');
                                    $toValue = data_get($filterValue, 'to');
                                @endphp
                                <div class="filters-field__range">
                                    <input
                                        type="date"
                                        name="{{ $filterKey }}[from]"
                                        class="form-control"
                                        value="{{ $fromValue }}"
                                        placeholder="From"
                                    >
                                    <span class="filters-field__range-sep">→</span>
                                    <input
                                        type="date"
                                        name="{{ $filterKey }}[to]"
                                        class="form-control"
                                        value="{{ $toValue }}"
                                        placeholder="To"
                                    >
                                </div>
                            @else
                                <input
                                    id="filter-{{ $filterKey }}"
                                    type="text"
                                    name="{{ $filterKey }}"
                                    value="{{ is_scalar($filterValue) ? $filterValue : '' }}"
                                    class="form-control"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    </div>
@endif
