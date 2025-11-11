@php
    $placeholder = $table->getSearchPlaceholder() ?? 'Search...';
@endphp

<form method="GET" class="resource-search-form">
    @foreach(['sort', 'dir'] as $hidden)
        @if(request()->filled($hidden))
            <input type="hidden" name="{{ $hidden }}" value="{{ request($hidden) }}">
        @endif
    @endforeach
    @if(request()->filled('q'))
        <input type="hidden" name="_previous_q" value="{{ request('q') }}">
    @endif

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
                placeholder="{{ $placeholder }}"
            >
        </div>
    </div>
</form>
