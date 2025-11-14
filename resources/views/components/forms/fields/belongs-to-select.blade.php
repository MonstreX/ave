{{-- resources/views/components/forms/fields/belongs-to-select.blade.php --}}
@php
    $fieldStatePath = $statePath ?? $field->getStatePath();
    $fieldInputName = $inputName ?? \Monstrex\Ave\Support\FormInputName::nameFromStatePath($fieldStatePath);
    $fieldInputId = $inputId ?? \Monstrex\Ave\Support\FormInputName::idFromStatePath($fieldStatePath);
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isNullable = $nullable ?? false;
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

    <select
        id="{{ $fieldInputId }}"
        name="{{ $fieldInputName }}"
        @if($isRequired) required @endif
        @if($isDisabled) disabled @endif
        @if($isReadonly) readonly @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >
        @if($isNullable)
            <option value="">{{ $emptyOption ?? '-- None --' }}</option>
        @endif

        @foreach($options as $option)
            <option
                value="{{ $option['value'] }}"
                @selected($option['value'] == $value)
            >
                {{ $option['label'] }}
            </option>
        @endforeach
    </select>

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
