{{-- resources/views/components/forms/fields/file.blade.php --}}
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

    <div class="custom-file-input">
        <input
            type="file"
            id="{{ $key }}"
            name="{{ $key }}{{ $isMultiple ? '[]' : '' }}"
            @if($isMultiple) multiple @endif
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            @if(!empty($acceptedMimes))
                accept="{{ implode(',', $acceptedMimes) }}"
            @endif
            class="file-input {{ $class ?? '' }}"
            {!! $attributes !!}
        >
        <span class="custom-file-label">
            <i class="voyager-download"></i>
            Choose file...
        </span>
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
