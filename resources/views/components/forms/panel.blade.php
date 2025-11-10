<div class="panel {{ $classes }}" {!! $attributes !!}>
    @if($header)
        <div class="panel-heading">
            <h3 class="panel-title">{{ $header }}</h3>
        </div>
    @endif

    <div class="panel-body">
        {!! $fieldsContent !!}
        {!! $componentsContent !!}
    </div>

    @if($footer)
        <div class="panel-footer">{{ $footer }}</div>
    @endif
</div>
