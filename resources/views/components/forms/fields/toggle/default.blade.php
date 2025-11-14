{{-- resources/views/components/forms/fields/toggle/default.blade.php --}}
@php
    $fieldStatePath = $statePath ?? $field->getStatePath();
    $fieldInputName = $inputName ?? \Monstrex\Ave\Support\FormInputName::nameFromStatePath($fieldStatePath);
    $fieldInputId = $inputId ?? \Monstrex\Ave\Support\FormInputName::idFromStatePath($fieldStatePath);
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isChecked = $checked ?? ($value ?? false);
    $size = $size ?? 'medium';
    $color = $color ?? 'primary';
    $toggleLabel = $toggleLabel ?? null;
@endphp

<div class="form-field form-field-toggle @if($hasError) has-error @endif" data-field-name="{{ $fieldStatePath }}">
    @if($labelText)
        <label class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="toggle-wrapper">
        <label class="toggle-label">
            <input type="hidden" name="{{ $fieldInputName }}" value="0">
            <input
                type="checkbox"
                id="{{ $fieldInputId }}"
                name="{{ $fieldInputName }}"
                value="1"
                @if($isChecked || old($fieldStatePath, false)) checked @endif
                @if($isRequired) required @endif
                @if($isDisabled) disabled @endif
                @if($isReadonly) readonly @endif
                class="toggle-input {{ $class ?? '' }}"
                {!! $attributes !!}
            >
            <span class="toggle-switch toggle-switch-{{ $size }} toggle-switch-{{ $color }}">
                <span class="toggle-slider"></span>
            </span>
            @if($toggleLabel)
                <span class="toggle-text">{{ $toggleLabel }}</span>
            @endif
        </label>
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
