{{-- resources/views/components/forms/text-input.blade.php --}}
@php
    $name = ($key ?? null) ?: $field->key();
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $inputType = $type ?? 'text';
    $inputValue = $value ?? $field->getValue();
    $placeholderText = $placeholder ?? null;
@endphp

<div class="form-field @if($hasError) has-error @endif">
    @if(!empty($labelText))
        <label for="{{ $name }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
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
                type="{{ $inputType }}"
                id="{{ $name }}"
                name="{{ $name }}"
                value="{{ $inputValue ?? '' }}"
                @if($isRequired) required @endif
                @if($isDisabled) disabled @endif
                @if($isReadonly) readonly @endif
                @if($placeholderText) placeholder="{{ $placeholderText }}" @endif
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
            type="{{ $inputType }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $inputValue ?? '' }}"
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            @if($placeholderText) placeholder="{{ $placeholderText }}" @endif
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

    @if(!empty($helpText))
        <div class="help-text">{{ $helpText }}</div>
    @endif
</div>
