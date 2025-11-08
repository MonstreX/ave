@php
    $id = 'toggle_' . uniqid();
    $isChecked = $checked ?? ($value ?? false) ?? old($key);
    $onLabel = $field->getOnLabel() ?? 'On';
    $offLabel = $field->getOffLabel() ?? 'Off';
@endphp

<div class="form-field @if($hasError) has-error @endif">
    @if($label ?? $field->getLabel())
        <label class="form-label">
            {{ $label ?? $field->getLabel() }}
            @if($required ?? $field->isRequired())<span class="required">*</span>@endif
        </label>
    @endif

    <div class="toggle @if(!$isChecked) off @endif"
         data-toggle="toggle"
         data-on="{{ $onLabel }}"
         data-off="{{ $offLabel }}">
        <input type="checkbox"
               id="{{ $id }}"
               name="{{ $key }}"
               class="toggleswitch"
               @if($isChecked) checked @endif
               @if($required ?? $field->isRequired()) required @endif
               @if($disabled ?? false) disabled @endif
               {!! $attributes !!}>
        <div class="toggle-group">
            <label class="toggle-on @if($isChecked) active @endif">{{ $onLabel }}</label>
            <label class="toggle-off @if(!$isChecked) active @endif">{{ $offLabel }}</label>
            <span class="toggle-handle"></span>
        </div>
    </div>

    @if(!empty($errors))
        <div class="error-message">
            @foreach($errors as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    @if($help ?? $field->getHelpText())
        <div class="help-text">{{ $help ?? $field->getHelpText() }}</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('{{ $id }}');
    const toggle = checkbox.closest('.toggle');
    const updateState = () => {
        toggle.classList.toggle('off', !checkbox.checked);
        toggle.querySelector('.toggle-on').classList.toggle('active', checkbox.checked);
        toggle.querySelector('.toggle-off').classList.toggle('active', !checkbox.checked);
    };

    updateState();
    checkbox.addEventListener('change', updateState);
    toggle.querySelector('.toggle-on').addEventListener('click', e => (e.preventDefault(), checkbox.checked = true, checkbox.dispatchEvent(new Event('change'))));
    toggle.querySelector('.toggle-off').addEventListener('click', e => (e.preventDefault(), checkbox.checked = false, checkbox.dispatchEvent(new Event('change'))));
    toggle.querySelector('.toggle-handle').addEventListener('click', e => (e.preventDefault(), checkbox.checked = !checkbox.checked, checkbox.dispatchEvent(new Event('change'))));
});
</script>
