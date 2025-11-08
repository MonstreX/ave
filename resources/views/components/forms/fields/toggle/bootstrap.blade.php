{{-- Bootstrap-style toggle with two buttons (On/Off) --}}
@php
    $labelText = $label ?? $field->getLabel();
    $helpText = ($help ?? null) ?: $field->getHelpText();
    $isRequired = $required ?? $field->isRequired();
    $isDisabled = $disabled ?? false;
    $isReadonly = $readonly ?? false;
    $isChecked = $checked ?? ($value ?? false);

    // Get on/off labels from field
    $onLabel = $field->getOnLabel() ?? 'On';
    $offLabel = $field->getOffLabel() ?? 'Off';

    $uniqueId = $key . '_' . uniqid();
@endphp

<div class="form-field form-field-toggle-bootstrap @if($hasError) has-error @endif">
    @if($labelText)
        <label class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="toggle-bootstrap-wrapper">
        {{-- Hidden input for form submission --}}
        <input
            type="hidden"
            name="{{ $key }}"
            value="0"
            class="toggle-bootstrap-hidden"
        >

        {{-- Actual checkbox (hidden visually) --}}
        <input
            type="checkbox"
            id="{{ $uniqueId }}"
            class="toggle-bootstrap-input"
            @if($isChecked || old($key, false)) checked @endif
            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
            @if($isReadonly) readonly @endif
            {!! $attributes !!}
        >

        {{-- Visual toggle buttons --}}
        <div class="toggle-bootstrap-group" data-toggle-id="{{ $uniqueId }}">
            <label class="toggle-bootstrap-btn toggle-bootstrap-on">
                {{ $onLabel }}
            </label>
            <label class="toggle-bootstrap-btn toggle-bootstrap-off">
                {{ $offLabel }}
            </label>
            <span class="toggle-bootstrap-handle"></span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleId = '{{ $uniqueId }}';
    const checkbox = document.getElementById(toggleId);
    const toggleGroup = document.querySelector('[data-toggle-id="' + toggleId + '"]');
    const onBtn = toggleGroup.querySelector('.toggle-bootstrap-on');
    const offBtn = toggleGroup.querySelector('.toggle-bootstrap-off');

    function updateToggleState() {
        if (checkbox.checked) {
            onBtn.classList.add('active');
            offBtn.classList.remove('active');
        } else {
            onBtn.classList.remove('active');
            offBtn.classList.add('active');
        }
    }

    // Initialize state
    updateToggleState();

    // Handle checkbox change
    checkbox.addEventListener('change', function() {
        updateToggleState();
    });

    // Handle button clicks
    onBtn.addEventListener('click', function(e) {
        e.preventDefault();
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));
    });

    offBtn.addEventListener('click', function(e) {
        e.preventDefault();
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
    });
});
</script>
