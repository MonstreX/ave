{{-- resources/views/components/forms/fields/file.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isMultiple = $multiple ?? false;
    $hasFile = !empty($value) && is_string($value) && file_exists(public_path($value));
    $fileName = $hasFile ? basename($value) : null;
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

    <div class="file-upload-wrapper"
         data-file-field="{{ $key }}"
         data-path-prefix="{{ $pathPrefix ?? '' }}"
         @if($customPath ?? false) data-custom-path="{{ $customPath }}" @endif>
        <!-- File preview if exists -->
        @if($hasFile)
            <div class="file-preview">
                <div class="file-item">
                    <div class="file-info">
                        <span class="file-name">{{ $fileName }}</span>
                        <span class="file-size" data-file-path="{{ $value }}"></span>
                    </div>
                    <button type="button" class="file-delete-btn" data-file-delete style="cursor: pointer;">
                        <i class="voyager-trash"></i> Remove
                    </button>
                </div>
            </div>
        @endif

        <div class="custom-file-input @if($hasFile) hidden @endif" data-file-input-area>
            <input
                type="file"
                id="{{ $key }}"
                name="{{ $key }}_file_upload"
                @if($isMultiple) multiple @endif
                @if($isRequired && !$hasFile) required @endif
                @if($isDisabled) disabled @endif
                @if($isReadonly) readonly @endif
                @if(!empty($acceptedMimes))
                    accept="{{ implode(',', $acceptedMimes) }}"
                @endif
                class="file-input {{ $class ?? '' }}"
                data-file-input
                {!! $attributes !!}
            >
            <span class="custom-file-label">
                <i class="voyager-download"></i>
                Choose file...
            </span>
        </div>

        <!-- Hidden input to store file path in the correct field name -->
        <input
            type="hidden"
            name="{{ $key }}"
            class="file-path-input"
            value="{{ $value ?? '' }}"
        >

        <!-- Upload progress -->
        <div class="file-upload-progress" data-file-progress style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <span class="progress-text">Uploading...</span>
        </div>
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
