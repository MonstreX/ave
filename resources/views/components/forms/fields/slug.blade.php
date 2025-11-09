@php
    $name = $field->key();
    $labelText = $field->getLabel();
    $helpText = $field->getHelpText();
    $isRequired = $field->isRequired();
    $isDisabled = $disabled ?? false;
    $inputValue = $value ?? $field->getValue();
@endphp

<div class="form-field slug-field">
    @if(!empty($labelText))
        <label for="{{ $name }}" class="form-label">
            {{ $labelText }}
            @if($isRequired)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="slug-input-wrapper" style="position: relative;">
        <input
            type="text"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $inputValue) }}"
            class="form-control slug-input @if($hasError) is-invalid @endif"

            {{-- Data attributes для JS --}}
            data-slug-field
            data-slug-source="{{ $field->getFrom() }}"
            data-slug-separator="{{ $field->getSeparator() }}"
            data-slug-locale="{{ $field->getLocale() }}"
            data-slug-api-url="{{ route('ave.api.slug') }}"

            @if($isRequired) required @endif
            @if($isDisabled) disabled @endif
        />

        {{-- Лоадер (спиннер) --}}
        <div class="slug-loader" style="display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none;">
            <svg class="slug-spinner" width="20" height="20" viewBox="0 0 50 50" style="animation: rotate 1s linear infinite;">
                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="80" stroke-dashoffset="60"></circle>
            </svg>
        </div>
    </div>

    @if($hasError)
        <div class="invalid-feedback d-block">
            @foreach($errors as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(!empty($helpText))
        <small class="form-text text-muted d-block mt-1">{{ $helpText }}</small>
    @endif
</div>

<style>
    .slug-input.is-loading {
        padding-right: 40px;
    }

    @keyframes rotate {
        100% {
            transform: rotate(360deg);
        }
    }
</style>
