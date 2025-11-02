{{-- resources/views/components/forms/select.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isMultiple = $multiple ?? false;
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

    <select
        id="{{ $key }}"
        name="{{ $key }}{{ $isMultiple ? '[]' : '' }}"
        @if($isMultiple) multiple size="{{ $size }}" @endif
        @if($isRequired) required @endif
        @if($isDisabled) disabled @endif
        @if($isReadonly) readonly @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >
        @if($emptyOption && !$multiple)
            <option value="">{{ $emptyOption }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option
                value="{{ $optionValue }}"
                @if(is_array($value) ? in_array($optionValue, $value) : $optionValue == $value) selected @endif
            >
                {{ $optionLabel }}
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
