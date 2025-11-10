{{-- Div component - universal container for grouping fields and components --}}
@php
    $classes = $classes ?? '';
    $attributes = $attributes ?? '';
    $header = $header ?? null;
    $footer = $footer ?? null;
    $fieldsContent = $fieldsContent ?? '';
    $componentsContent = $componentsContent ?? '';
@endphp

<div class="{{ $classes }}" {!! $attributes !!}>
    {{-- Optional header --}}
    @if($header)
        <div class="div-header">
            {{ $header }}
        </div>
    @endif

    {{-- Direct child fields (rendered first) --}}
    @if(!empty(trim($fieldsContent)))
        {!! $fieldsContent !!}
    @endif

    {{-- Child components/containers (nested divs, etc.) --}}
    @if(!empty(trim($componentsContent)))
        {!! $componentsContent !!}
    @endif

    {{-- Optional footer --}}
    @if($footer)
        <div class="div-footer">
            {{ $footer }}
        </div>
    @endif
</div>
