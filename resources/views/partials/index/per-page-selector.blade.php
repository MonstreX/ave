@php
    $perPageOptions = $table->getPerPageOptions();
    $currentPerPage = $records->perPage();
    $showSelector = $table->shouldShowPerPageSelector();
@endphp

@if($showSelector && count($perPageOptions) > 1)
    <div class="per-page-selector">
        <label for="per-page-select-{{ $slug }}" class="per-page-label">
            {{ __('ave::tables.show') }}
        </label>
        <select
            id="per-page-select-{{ $slug }}"
            class="per-page-select"
            data-slug="{{ $slug }}"
        >
            @foreach($perPageOptions as $option)
                <option
                    value="{{ $option }}"
                    {{ $option == $currentPerPage ? 'selected' : '' }}
                >
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="per-page-suffix">{{ __('ave::tables.per_page') }}</span>
    </div>
@endif
