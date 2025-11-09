{{-- resources/views/components/forms/fields/belongs-to-many-select.blade.php --}}
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

    <select
        id="{{ $key }}"
        name="{{ $key }}[]"
        multiple
        @if($isRequired) required @endif
        @if($isDisabled) disabled @endif
        @if($isReadonly) readonly @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >
        @foreach($options as $option)
            <option
                value="{{ $option['value'] }}"
                @selected(in_array($option['value'], $value ?? []))
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
