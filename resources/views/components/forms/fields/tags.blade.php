{{-- resources/views/components/forms/fields/tags.blade.php --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $tagsArray = is_array($value ?? null) ? ($value ?? []) : (!empty($value) ? explode(',', $value) : []);
    $fieldId = $key . '_input';
@endphp

<div class="form-field @if($hasError) has-error @endif">
    @if($labelText)
        <label for="{{ $fieldId }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="chip-input" data-tags-field="{{ $key }}" role="region" aria-label="Tags input">
        @foreach($tagsArray as $tag)
            <div class="chip" tabindex="0">
                <span class="chip-text">{{ trim($tag) }}</span>
                <span class="chip-remove" data-tag-remove style="cursor: pointer;">&times;</span>
            </div>
        @endforeach

        <input
            type="text"
            id="{{ $fieldId }}"
            name="{{ $key }}_input"
            placeholder="Add tags..."
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="tags-input {{ $class ?? '' }}"
            data-tags-separator="{{ $separator ?? ',' }}"
            data-tags-key="{{ $key }}"
            autocomplete="off"
            tabindex="0"
            {!! $attributes !!}
        >
    </div>

    <!-- Hidden input for actual form submission -->
    <input
        type="hidden"
        name="{{ $key }}"
        class="tags-hidden-input"
        value="{{ is_array($value ?? null) ? implode(',', $value ?? []) : ($value ?? '') }}"
    >

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
