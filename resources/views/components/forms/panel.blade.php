<div class="panel panel-bordered">
    @if($title)
        <div class="panel-heading">
            <h3 class="panel-title">{{ $title }}</h3>
            @if($description)
                <p class="panel-subtitle text-muted">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div class="panel-body">
        {!! $content !!}
    </div>
</div>
