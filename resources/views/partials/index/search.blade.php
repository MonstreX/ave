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
