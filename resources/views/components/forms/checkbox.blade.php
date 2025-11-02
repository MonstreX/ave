{{-- resources/views/components/forms/checkbox.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isChecked = $checked ?? ($value ?? false);
@endphp

<div class="form-field form-field-checkbox @if($hasError) has-error @endif">
    @if($labelText)
        <label class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="checkbox-wrapper">
        <label class="checkbox-label">
            <input type="hidden" name="{{ $key }}" value="0">
            <input
                type="checkbox"
                id="{{ $key }}"
                name="{{ $key }}"
                value="1"
                @if($isChecked || old($key, false)) checked @endif
                @if($isRequired) required @endif
                @if($isDisabled) disabled @endif
                @if($isReadonly) readonly @endif
                class="checkbox-input {{ $class ?? '' }}"
                {!! $attributes !!}
            >
            <span class="checkbox-custom"></span>
            @if($checkboxLabel)
                <span class="checkbox-text">{{ $checkboxLabel }}</span>
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
