<fieldset>
    @if($label)
        <legend>{{ $label }}</legend>
    @endif

    @if($description)
        <p class="text-muted">{{ $description }}</p>
    @endif

    {!! $content !!}
</fieldset>
