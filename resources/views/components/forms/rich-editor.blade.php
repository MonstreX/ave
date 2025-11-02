{{-- resources/views/components/forms/rich-editor.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $editorHeight = $height ?? 400;
    $editorValue = $value ?? $field->getValue();
@endphp

<div class="form-field @if($hasError) has-error @endif" data-field-type="rich-editor">
    @if($labelText)
        <label for="{{ $key }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div wire:ignore>
        <textarea
            id="{{ $key }}"
            name="{{ $key }}"
            data-editor="rich"
            data-height="{{ $editorHeight }}"
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            {!! $attributes !!}
        >{{ $value ?? '' }}</textarea>
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

@include('ave::partials.editors-assets')

