{{-- resources/views/components/forms/fields/color-picker.blade.php --}}
@php
    $fieldStatePath = $statePath ?? $field->getStatePath();
    $fieldInputName = $inputName ?? \Monstrex\Ave\Support\FormInputName::nameFromStatePath($fieldStatePath);
    $fieldInputId = $inputId ?? \Monstrex\Ave\Support\FormInputName::idFromStatePath($fieldStatePath);
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $colorValue = $value ?? $field->getValue() ?? '#000000';
@endphp

<div class="form-field @if($hasError) has-error @endif" data-field-name="{{ $fieldStatePath }}">
    @if($labelText)
        <label for="{{ $fieldInputId }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="color-picker-wrapper">
        <input
            type="color"
            id="{{ $fieldInputId }}"
            name="{{ $fieldInputName }}"
            value="{{ $colorValue }}"
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            class="form-control color-picker-input {{ $class ?? '' }}"
            {!! $attributes !!}
        >
        <span class="color-picker-value">{{ $colorValue }}</span>
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
