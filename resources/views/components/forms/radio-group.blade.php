{{-- resources/views/components/forms/radio-group.blade.php --}}
<div class="form-field form-field-radio-group @if($hasError) has-error @endif">
    @if($label)
        <label class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="radio-group {{ $inline ? 'radio-group-inline' : '' }}">
        @foreach($options as $optionValue => $optionLabel)
            <label class="radio-label">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $optionValue }}"
                    @if($optionValue == old($name, $value ?? '')) checked @endif
                    @if($required) required @endif
                    @if($disabled) disabled @endif
                    @if($readonly) readonly @endif
                    class="radio-input {{ $class ?? '' }}"
                    {!! $attributes !!}
                >
                <span class="radio-custom"></span>
                <span class="radio-text">{{ $optionLabel }}</span>
            </label>
        @endforeach
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
