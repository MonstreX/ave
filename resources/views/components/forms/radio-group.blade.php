{{-- resources/views/components/forms/radio-group.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isInline = $inline ?? false;
@endphp

<div class="form-field form-field-radio-group @if($hasError) has-error @endif">
    @if($labelText)
        <label class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="radio-group {{ $isInline ? 'radio-group-inline' : '' }}">
        @foreach($options as $optionValue => $optionLabel)
            <label class="radio-label">
                <input
                    type="radio"
                    name="{{ $key }}"
                    value="{{ $optionValue }}"
                    @if($optionValue == old($key, $value ?? '')) checked @endif
                    @if($isRequired) required @endif
                    @if($isDisabled) disabled @endif
                    @if($isReadonly) readonly @endif
                    class="radio-input {{ $class ?? '' }}"
                    {!! $attributes !!}
                >
                <span class="radio-custom"></span>
                <span class="radio-text">{{ $optionLabel }}</span>
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
