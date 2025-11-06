{{-- resources/views/components/forms/fields/code-editor.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
@endphp

<div class="form-field @if($hasError) has-error @endif" data-field-type="code-editor">
    @if($labelText)
        <label for="{{ $key }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="code-editor-wrapper" data-theme="{{ $theme }}">
        <textarea
            id="{{ $key }}"
            name="{{ $key }}"
            class="code-editor-field"
            data-language="{{ $language }}"
            data-height="{{ $height }}"
            data-theme="{{ $theme }}"
            data-auto-height="{{ $autoHeight ? 'true' : 'false' }}"
            data-line-numbers="{{ $lineNumbers ? 'true' : 'false' }}"
            data-code-folding="{{ $codeFolding ? 'true' : 'false' }}"
            data-auto-complete="{{ $autoComplete ? 'true' : 'false' }}"
            data-tab-size="{{ $tabSize }}"
            style="display: none;"
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
        >{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($value ?? '') }}</textarea>

        <!-- CodeMirror will be mounted here -->
        <div class="code-editor-content" data-editor-target="{{ $key }}"></div>
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
