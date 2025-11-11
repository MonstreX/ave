@php
    $filters = isset($table) && method_exists($table, 'getFilters') ? $table->getFilters() : [];
    $hasFilters = !empty($filters);
    $filterKeys = array_map(fn($filter) => $filter->key(), $filters);
    $hasActiveFilters = false;
    foreach ($filterKeys as $key) {
        $value = request()->input($key);
        if (is_array($value)) {
            $value = array_filter($value, fn($v) => $v !== null && $v !== '');
        }
        if ($value !== null && $value !== '' && $value !== []) {
            $hasActiveFilters = true;
            break;
        }
    }
@endphp

<form method="GET" class="filters-inline-form">
    @if($hasFilters)
        <div class="filters-inline-grid">
            @foreach($filters as $filter)
                @php
                    $filterKey = $filter->key();
                    $filterValue = request()->input($filterKey);
                    $filterData = method_exists($filter, 'toArray') ? $filter->toArray() : [];
                    $filterLabel = $filterData['label'] ?? ucfirst(str_replace('_', ' ', $filterKey));
                @endphp
                <div class="filters-inline-field" data-filter="{{ $filterKey }}">
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
                            class="form-control form-control-sm"
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
                                class="form-control form-control-sm"
                                value="{{ $fromValue }}"
                                placeholder="From"
                            >
                            <span class="filters-field__range-sep">→</span>
                            <input
                                type="date"
                                name="{{ $filterKey }}[to]"
                                class="form-control form-control-sm"
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
                            class="form-control form-control-sm"
                        >
                    @endif
                </div>
            @endforeach
        </div>
    @endif

        <div class="filters-inline-actions">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <input type="hidden" name="sort" value="{{ request('sort') }}">
            <input type="hidden" name="dir" value="{{ request('dir') }}">

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="voyager-filter"></i> <span>Apply</span>
            </button>

            @if($hasActiveFilters)
                <a href="{{ route('ave.resource.index', ['slug' => $slug]) }}" class="btn btn-default btn-sm">
                    <i class="voyager-refresh"></i> <span>Reset</span>
                </a>
            @endif
        </div>
</form>
