{{-- resources/views/components/forms/code-editor.blade.php --}}
<div class="form-field @if($hasError) has-error @endif" data-field-type="code-editor">
    @if($label)
        <label for="{{ $key }}" class="form-label">
            {{ $label }}
            @if($required)
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
            data-line-numbers="{{ $lineNumbers ? 'true' : 'false' }}"
            data-code-folding="{{ $codeFolding ? 'true' : 'false' }}"
            data-auto-complete="{{ $autoComplete ? 'true' : 'false' }}"
            data-tab-size="{{ $tabSize }}"
            style="display: none;"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
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

    @if($helpText)
        <div class="help-text">{{ $helpText }}</div>
    @endif
</div>

@include('ave::partials.editors-assets')

