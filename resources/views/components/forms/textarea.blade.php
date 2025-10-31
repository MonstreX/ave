{{-- resources/views/components/forms/textarea.blade.php --}}
<div class="form-field @if($hasError) has-error @endif">
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif
    
    <textarea 
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows ?? 4 }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($autosize ?? false) data-autosize="true" @endif
        class="form-control {{ $class ?? '' }}"
        {!! $attributes !!}
    >{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($value ?? '') }}</textarea>
    
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
