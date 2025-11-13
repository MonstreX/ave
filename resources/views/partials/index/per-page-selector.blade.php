@php
    $perPageOptions = $table->getPerPageOptions();
    $currentPerPage = $records->perPage();
    $showSelector = $table->shouldShowPerPageSelector();
@endphp

@if($showSelector && count($perPageOptions) > 1)
    <div class="per-page-selector">
        <label for="per-page-select-{{ $slug }}" class="per-page-label">
            Show:
        </label>
        <select
            id="per-page-select-{{ $slug }}"
            class="per-page-select"
            data-slug="{{ $slug }}"
            data-endpoint="{{ route('ave.resource.set-per-page', ['slug' => $slug]) }}"
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
        <span class="per-page-suffix">per page</span>
    </div>
@endif
