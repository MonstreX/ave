@php
    $fieldId = $fieldId ?? 'ave-filter-search';
    $placeholder = $table->getSearchPlaceholder() ?? 'Search...';
@endphp

<div class="filters-field filters-field--search form-group">
    <label class="control-label" for="{{ $fieldId }}">{{ $placeholder }}</label>
    <div class="input-group">
        <span class="input-group-addon">
            <i class="voyager-search"></i>
        </span>
        <input
            id="{{ $fieldId }}"
            type="text"
            name="q"
            value="{{ request('q') }}"
            class="form-control"
            placeholder="{{ $placeholder }}"
        >
    </div>
</div>
