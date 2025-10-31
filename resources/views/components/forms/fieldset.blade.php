{{-- resources/views/components/forms/fieldset.blade.php --}}
<div class="form-field fieldset-field @if($hasError) has-error @endif" data-field-name="{{ $name }}">
    @if($label)
        <label class="form-label">
            {{ $label }}
            @if($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="fieldset-container"
         data-fieldset
         data-sortable="{{ $sortable ? 'true' : 'false' }}"
         data-collapsible="{{ $collapsible ? 'true' : 'false' }}"
         data-collapsed="{{ $collapsed ? 'true' : 'false' }}"
         data-min-items="{{ $minItems }}"
         data-max-items="{{ $maxItems ?? '' }}"
         data-field-name="{{ $name }}">

        {{-- Action Bar --}}
        <div class="fieldset-actions">
            <div class="fieldset-actions-left">
                @if($collapsible)
                    <button type="button" class="btn-fieldset-action" data-action="collapse-all" title="Collapse All">
                        <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6l4 4 4-4M4 2l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Collapse All</span>
                    </button>
                    <button type="button" class="btn-fieldset-action" data-action="expand-all" title="Expand All">
                        <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 10l4-4 4 4M4 14l4-4 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Expand All</span>
                    </button>
                @endif

                @if($sortable)
                    <label class="fieldset-sort-toggle">
                        <input type="checkbox" data-action="toggle-sort">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Sort Mode</span>
                    </label>
                @endif
            </div>

            <div class="fieldset-actions-right">
                <button type="button" class="btn btn-fieldset-add" data-action="add">
                    <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>{{ $addButtonLabel }}</span>
                </button>
            </div>
        </div>

        {{-- Existing items --}}
        <div class="fieldset-items" data-fieldset-items>
            @foreach($itemInstances as $index => $fields)
                <div class="fieldset-item{{ $collapsed ? ' collapsed' : '' }}" data-item-index="{{ $index }}">
                    @if($sortable)
                        <div class="fieldset-drag-handle" title="Drag to reorder">
                            <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <circle cx="6" cy="4" r="1" fill="currentColor"/>
                                <circle cx="10" cy="4" r="1" fill="currentColor"/>
                                <circle cx="6" cy="8" r="1" fill="currentColor"/>
                                <circle cx="10" cy="8" r="1" fill="currentColor"/>
                                <circle cx="6" cy="12" r="1" fill="currentColor"/>
                                <circle cx="10" cy="12" r="1" fill="currentColor"/>
                            </svg>
                        </div>
                    @endif

                    <div class="fieldset-item-content">
                        <div class="fieldset-item-header"
                             data-head-title-field="{{ $headTitle }}"
                             data-head-preview-field="{{ $headPreview }}">
                            @if($collapsible)
                                <button type="button" class="btn-fieldset-collapse" data-action="collapse" title="Expand/Collapse">
                                    <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            @endif
                            <span class="fieldset-item-number">{{ $index + 1 }}</span>

                            @if($headPreview)
                                <div class="fieldset-item-preview" data-item-preview></div>
                            @endif

                            @if($headTitle)
                                <span class="fieldset-item-title" data-item-title></span>
                            @endif

                            <button type="button" class="btn-fieldset-delete" data-action="delete" title="{{ $deleteButtonLabel }}">
                                <svg class="icon" width="18" height="18" viewBox="0 0 16 16" fill="none">
                                    <path d="M2 4h12M5.5 4V2.5A1.5 1.5 0 0 1 7 1h2a1.5 1.5 0 0 1 1.5 1.5V4m2 0v10a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 2.5 14V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    <path d="M6.5 7v4M9.5 7v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>

                        <div class="fieldset-item-fields">
                            {{-- Hidden field to store unique item ID (never changes with sorting) --}}
                            <input type="hidden" name="{{ $name }}[{{ $index }}][_id]" value="{{ $itemIds[$index] ?? $index + 1 }}">

                            @foreach($fields as $field)
                                {!! $field->render($context) !!}
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
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

{{-- Template for new items --}}
<template id="fieldset-template-{{ $name }}">
    <div class="fieldset-item{{ $collapsed ? ' collapsed' : '' }}" data-item-index="__INDEX__">
        @if($sortable)
            <div class="fieldset-drag-handle" title="Drag to reorder">
                <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <circle cx="6" cy="4" r="1" fill="currentColor"/>
                    <circle cx="10" cy="4" r="1" fill="currentColor"/>
                    <circle cx="6" cy="8" r="1" fill="currentColor"/>
                    <circle cx="10" cy="8" r="1" fill="currentColor"/>
                    <circle cx="6" cy="12" r="1" fill="currentColor"/>
                    <circle cx="10" cy="12" r="1" fill="currentColor"/>
                </svg>
            </div>
        @endif

        <div class="fieldset-item-content">
            <div class="fieldset-item-header"
                 data-head-title-field="{{ $headTitle }}"
                 data-head-preview-field="{{ $headPreview }}">
                @if($collapsible)
                    <button type="button" class="btn-fieldset-collapse" data-action="collapse" title="Expand/Collapse">
                        <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                @endif
                <span class="fieldset-item-number"></span>

                @if($headPreview)
                    <div class="fieldset-item-preview" data-item-preview></div>
                @endif

                @if($headTitle)
                    <span class="fieldset-item-title" data-item-title></span>
                @endif

                <button type="button" class="btn-fieldset-delete" data-action="delete" title="{{ $deleteButtonLabel }}">
                    <svg class="icon" width="18" height="18" viewBox="0 0 16 16" fill="none">
                        <path d="M2 4h12M5.5 4V2.5A1.5 1.5 0 0 1 7 1h2a1.5 1.5 0 0 1 1.5 1.5V4m2 0v10a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 2.5 14V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M6.5 7v4M9.5 7v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="fieldset-item-fields">
                {{-- Hidden field to store unique item ID (value will be set by JS) --}}
                <input type="hidden" name="{{ $name }}[__INDEX__][_id]" value="" data-field-id>

                {{-- Template fields with __INDEX__ placeholder --}}
                @foreach($templateFields as $templateField)
                    {!! $templateField->render($context) !!}
                @endforeach
            </div>
        </div>
    </div>
</template>
