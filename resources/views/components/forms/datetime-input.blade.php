{{-- Custom datetime field view --}}
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

    <input
        type="datetime-local"
        id="{{ $key }}"
        name="{{ $key }}"
        value="{{ $value ?? '' }}"
        @if($isRequired) required @endif
        @if($isDisabled) disabled @endif
        @if($isReadonly) readonly @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >

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
