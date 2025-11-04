{{-- resources/views/components/forms/media.blade.php --}}
<div class="form-field media-field @if($hasError) has-error @endif" data-field-name="{{ $key }}">
    @if($label)
        <label for="{{ $key }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="media-field-container"
         style="--media-columns: {{ $columns ?? 6 }};"
         data-multiple="{{ $multiple ? 'true' : 'false' }}"
         data-max-files="{{ $maxFiles ?? '' }}"
         data-max-size="{{ $maxFileSize ?? '' }}"
         data-accept="{{ $acceptString }}"
         data-upload-url="{{ $uploadUrl }}"
         data-collection="{{ $collection }}"
         data-model-type="{{ $modelType ?? '' }}"
         data-model-id="{{ $modelId ?? '' }}"
         data-meta-key="{{ $metaKey ?? '' }}"
         data-prop-names="{{ json_encode($propNames ?? []) }}">

        {{-- Upload Area --}}
        <div class="media-upload-area" data-media-dropzone>
            <input type="file"
                   id="{{ $key }}_file_input"
                   name="{{ $key }}_files[]"
                   @if($multiple) multiple @endif
                   @if(!empty($accept)) accept="{{ $acceptString }}" @endif
                   class="media-file-input"
                   style="display: none;">

            <div class="upload-prompt">
                <svg class="upload-icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#upload') }}"></use></svg>
                <div class="upload-messages">
                    <p class="upload-text">
                        <span class="upload-link">Click to upload</span> or drag and drop
                    </p>
                    @if(!empty($accept) || $maxFileSize)
                        <p class="upload-hint">
                            @if(!empty($accept))
                                {{ implode(', ', array_map(fn($type) => strtoupper(str_replace(['application/', 'image/'], '', $type)), $accept)) }}
                            @endif
                            @if($maxFileSize)
                                (max {{ number_format($maxFileSize / 1024, 1) }} MB)
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <p class="progress-text">Uploading...</p>
            </div>
        </div>

        {{-- Media Items Grid --}}
        <div class="media-items-grid" data-media-grid>
            @foreach($mediaItems as $index => $media)
                <div class="media-item" data-media-id="{{ $media->id }}">
                    <div class="media-order">
                        {{ $index + 1 }}
                    </div>
                    <div class="media-preview media-drag-handle">
                        @if(str_starts_with($media->mime_type, 'image/'))
                            <img src="{{ $media->url() }}" alt="{{ $media->prop('title') ?? $media->file_name }}">
                        @else
                            <div class="media-file-icon">
                                <svg class="icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#file') }}"></use></svg>
                                <span class="media-file-name">{{ $media->file_name }}</span>
                            </div>
                        @endif

                        <div class="media-action-holder">
                            @if(!empty($propNames))
                                <button type="button" class="media-action" data-action="edit" title="Edit">
                                    <svg class="icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#edit') }}"></use></svg>
                                </button>
                            @endif
                            <button type="button" class="media-action" data-action="delete" title="Delete">
                                <svg class="icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#delete') }}"></use></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Hidden inputs for order and deletion tracking --}}
                    <input type="hidden" name="__media_order[{{ $metaKey }}][]" value="{{ $media->id }}">

                    {{-- Hidden input with current props (for edit form pre-filling) --}}
                    @php
                        $currentProps = is_string($media->props) ? json_decode($media->props, true) : [];
                    @endphp
                    <input type="hidden"
                           name="__media_props[{{ $metaKey }}][{{ $media->id }}]"
                           value="{{ json_encode($currentProps ?: []) }}"
                           data-media-props="true"
                           data-props-id="{{ $media->id }}">

                    <div class="media-item-footer">
                        <div class="media-item-footer-line">
                            <div class="media-filename">{{ $media->file_name }}</div>
                            <div class="media-size {{ $media->size > 1048576 ? 'media-large-file' : '' }}">
                                {{ humanFileSize($media->size) }}
                            </div>
                        </div>
                        <div class="media-title"><i>{{ $media->prop('title') }}</i></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Hidden inputs for tracking changes --}}
        <input type="hidden" name="__media_uploaded[{{ $metaKey }}]" value="" data-uploaded-ids>
        <input type="hidden" name="__media_deleted[{{ $metaKey }}]" value="" data-deleted-ids>
    </div>

    @if(!empty($errors))
        <div class="error-message">
            @foreach($errors as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if($help)
        <div class="help-text">{{ $help }}</div>
    @endif
</div>

{{-- Template for dynamically added media items --}}
<template id="media-item-template-{{ $key }}">
    <div class="media-item" data-media-id="">
        <div class="media-order"></div>
        <div class="media-preview @if($multiple) media-drag-handle @endif">
            {{-- Image preview (shown for images) --}}
            <img class="media-image" src="" alt="" style="display: none;">

            {{-- File icon (shown for non-images) --}}
            <div class="media-file-icon" style="display: none;">
                <svg class="icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#file') }}"></use></svg>
                <span class="media-file-name"></span>
            </div>

            <div class="media-action-holder">
                @if(!empty($propNames))
                    <button type="button" class="media-action" data-action="edit" title="Edit">
                        <svg class="icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#edit') }}"></use></svg>
                    </button>
                @endif
                <button type="button" class="media-action" data-action="delete" title="Delete">
                    <svg class="icon"><use href="{{ asset('vendor/ave/assets/images/icons/sprite.svg#delete') }}"></use></svg>
                </button>
            </div>
        </div>

        <input type="hidden" name="__media_order[{{ $metaKey }}][]" value="">

        <div class="media-item-footer">
            <div class="media-item-footer-line">
                <div class="media-filename"></div>
                <div class="media-size"></div>
            </div>
            <div class="media-title"><i></i></div>
        </div>
    </div>
</template>

@include('ave::partials.editors-assets')
