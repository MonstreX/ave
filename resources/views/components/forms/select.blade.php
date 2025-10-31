{{-- resources/views/components/forms/select.blade.php --}}
<div class="form-field @if($hasError) has-error @endif">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        @if($multiple) multiple size="{{ $size }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >
        @if($emptyOption && !$multiple)
            <option value="">{{ $emptyOption }}</option>
        @endif

        @foreach($options as $optionValue => $optionLabel)
            <option
                value="{{ $optionValue }}"
                @if(is_array($value) ? in_array($optionValue, $value) : $optionValue == $value) selected @endif
            >
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

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
