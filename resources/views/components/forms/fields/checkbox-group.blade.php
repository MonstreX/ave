{{-- resources/views/components/forms/fields/checkbox-group.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isInline = $inline ?? false;
    $selectedValues = (is_array($value ?? null)) ? $value : (!empty($value) ? [$value] : []);
@endphp

<div class="form-field form-field-checkbox-group @if($hasError) has-error @endif">
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
                    name="{{ $key }}[]"
                    value="{{ $optionValue }}"
                    @if(in_array($optionValue, $selectedValues) || in_array($optionValue, (array) old($key, []))) checked @endif
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
