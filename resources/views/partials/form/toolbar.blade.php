<div class="page-header">
    <h1 class="page-title">
        <i class="{{ $isEdit ? 'voyager-edit' : 'voyager-plus' }}"></i>
        {{ $titleLabel }} {{ $resource::getSingularLabel() }}
    </h1>
    <div class="page-header-actions">
        <a href="{{ $cancelUrl }}" class="btn btn-secondary">
            <i class="voyager-angle-left"></i>
            <span>{{ __('Back') }}</span>
        </a>
    </div>
</div>
