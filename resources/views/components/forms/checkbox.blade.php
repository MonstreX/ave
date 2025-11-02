{{-- resources/views/components/forms/checkbox.blade.php --}}
<div class="form-field form-field-checkbox @if($hasError) has-error @endif">
    @if($label)
        <label class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="checkbox-wrapper">
        <label class="checkbox-label">
            <input type="hidden" name="{{ $key }}" value="0">
            <input
                type="checkbox"
                id="{{ $key }}"
                name="{{ $key }}"
                value="1"
                @if($checked || old($key, $value ?? false)) checked @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                class="checkbox-input {{ $class ?? '' }}"
                {!! $attributes !!}
            >
            <span class="checkbox-custom"></span>
            @if($checkboxLabel)
                <span class="checkbox-text">{{ $checkboxLabel }}</span>
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
