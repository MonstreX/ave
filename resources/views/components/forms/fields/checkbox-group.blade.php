{{-- resources/views/components/forms/fields/checkbox-group.blade.php --}}
@php
    $fieldStatePath = $statePath ?? $field->getStatePath();
    $fieldInputName = $inputName ?? \Monstrex\Ave\Support\FormInputName::nameFromStatePath($fieldStatePath);
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isInline = $inline ?? false;

    // Parse comma-separated string to array
    if (is_array($value ?? null)) {
        $selectedValues = $value;
    } elseif (!empty($value)) {
        $selectedValues = array_filter(array_map('trim', explode(',', $value)));
    } else {
        $selectedValues = [];
    }
@endphp

<div class="form-field form-field-checkbox-group @if($hasError) has-error @endif" data-field-name="{{ $fieldStatePath }}">
    @if($labelText)
        <label class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="checkbox-group {{ $isInline ? 'checkbox-group-inline' : '' }}">
        @foreach($options as $optionValue => $optionLabel)
            <label class="checkbox-label">
                <input
                    type="checkbox"
                    name="{{ $fieldInputName }}[]"
                    value="{{ $optionValue }}"
                    @if(in_array($optionValue, $selectedValues) || in_array($optionValue, (array) old($fieldStatePath, []))) checked @endif
                    @if($isRequired) required @endif
                    @if($isDisabled) disabled @endif
                    @if($isReadonly) readonly @endif
                    class="checkbox-input {{ $class ?? '' }}"
                    {!! $attributes !!}
                >
                <span class="checkbox-custom"></span>
                <span class="checkbox-text">{{ $optionLabel }}</span>
            </label>
        @endforeach
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
