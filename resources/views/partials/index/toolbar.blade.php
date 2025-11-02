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
