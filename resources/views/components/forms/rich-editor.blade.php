{{-- resources/views/components/forms/rich-editor.blade.php --}}
<div class="form-field @if($hasError) has-error @endif" data-field-type="rich-editor">
    @if($label)
        <label for="{{ $key }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div wire:ignore>
        <textarea
            id="{{ $key }}"
            name="{{ $key }}"
            data-editor="rich"
            data-height="{{ $height ?? 400 }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {!! $attributes !!}
        >{{ $value ?? '' }}</textarea>
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

@include('ave::partials.editors-assets')

