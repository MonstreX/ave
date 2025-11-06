{{-- resources/views/components/forms/fields/textarea.blade.php --}}
@php
    $name = ($key ?? null) ?: $field->key();
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $inputValue = $value ?? $field->getValue();
    $placeholderText = $placeholder ?? null;
    $rowsCount = $rows ?? 4;
@endphp

<div class="form-field @if($hasError) has-error @endif">
    @if(!empty($labelText))
        <label for="{{ $name }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rowsCount }}"
        @if($isRequired) required @endif
        @if($isDisabled) disabled @endif
        @if($isReadonly) readonly @endif
        @if($placeholderText) placeholder="{{ $placeholderText }}" @endif
        @if(!empty($maxLength)) maxlength="{{ $maxLength }}" @endif
        @if(!empty($autosize)) data-autosize="true" @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >{{ is_array($inputValue) ? json_encode($inputValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($inputValue ?? '') }}</textarea>

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
