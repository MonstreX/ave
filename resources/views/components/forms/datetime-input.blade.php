{{-- Custom datetime field view --}}
<div class="form-field @if($hasError) has-error @endif">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <input
        type="datetime-local"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ $value ?? '' }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >

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
