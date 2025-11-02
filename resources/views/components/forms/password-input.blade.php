{{-- resources/views/components/forms/password-input.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
@endphp

<div class="form-field @if($hasError) has-error @endif">
    @if($labelText)
        <label for="{{ $key }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="password-input-wrapper">
        <input
            type="password"
            id="{{ $key }}"
            name="{{ $key }}"
            value="{{ $value ?? '' }}"
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="form-control {{ $class ?? '' }}"
            data-password-input
            {!! $attributes !!}
        >
        @if($showToggle ?? true)
            <button type="button" class="password-toggle" data-password-toggle aria-label="Toggle password visibility">
                <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
            </button>
        @endif
    </div>

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
