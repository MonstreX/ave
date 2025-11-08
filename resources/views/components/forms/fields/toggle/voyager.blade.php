{{-- Voyager-style toggle checkbox (based on TCG Voyager CheckboxHandler) --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isChecked = $checked ?? ($value ?? false);
    $voyagerClass = $class ?? 'toggleswitch';
@endphp

<div class="form-field form-field-toggle @if($hasError) has-error @endif">
    @if($labelText)
        <label class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="voyager-checkbox-wrapper">
        <input
            type="checkbox"
            name="{{ $key }}"
            class="{{ $voyagerClass }}"
            value="1"
            @if($isChecked || old($key, false)) checked @endif
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            {!! $attributes !!}
        >
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
