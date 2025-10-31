{{-- resources/views/components/forms/text-input.blade.php --}}
<div class="form-field @if($hasError) has-error @endif">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif
    
    @php
        $hasPrefix = isset($prefix) && $prefix !== '';
        $hasSuffix = isset($suffix) && $suffix !== '';
    @endphp

    @if($hasPrefix || $hasSuffix)
        <div class="input-affix">
            @if($hasPrefix)
                <span class="input-affix-prefix">{{ $prefix }}</span>
            @endif

            <input 
                type="{{ $type ?? 'text' }}"
                id="{{ $name }}"
                name="{{ $name }}"
                value="{{ $value ?? '' }}"
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                class="form-control {{ $class ?? '' }} input-affix-control"
                @if(!empty($slugSource)) data-ave-slug-source="{{ $slugSource }}" @endif
                {!! $attributes !!}
            >

            @if($hasSuffix)
                <span class="input-affix-suffix">{{ $suffix }}</span>
            @endif
        </div>
    @else
        <input 
            type="{{ $type ?? 'text' }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $value ?? '' }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="form-control {{ $class ?? '' }}"
            @if(!empty($slugSource)) data-ave-slug-source="{{ $slugSource }}" @endif
            {!! $attributes !!}
        >
    @endif
    
    @if(!empty($errors))
        <div class="error-message">
            @foreach($errors as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

@if($helpText)
    <div class="help-text">{{ $helpText }}</div>
@endif
</div>

