{{-- resources/views/components/forms/toggle.blade.php --}}
<div class="form-field form-field-toggle @if($hasError) has-error @endif">
    @if($label)
        <label class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="toggle-wrapper">
        <label class="toggle-label">
            <input type="hidden" name="{{ $name }}" value="0">
            <input
                type="checkbox"
                id="{{ $name }}"
                name="{{ $name }}"
                value="1"
                @if($checked || old($name, $value ?? false)) checked @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                class="toggle-input {{ $class ?? '' }}"
                {!! $attributes !!}
            >
            <span class="toggle-switch toggle-switch-{{ $size }} toggle-switch-{{ $color }}">
                <span class="toggle-slider"></span>
            </span>
            @if($toggleLabel)
                <span class="toggle-text">{{ $toggleLabel }}</span>
            @endif
        </label>
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
