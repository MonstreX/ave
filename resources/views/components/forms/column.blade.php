@php
    $span = max(1, min(12, $span ?? 12));
    $class = 'col-md-' . $span;
@endphp

<div class="{{ $class }}">
    {!! $content !!}
</div>
