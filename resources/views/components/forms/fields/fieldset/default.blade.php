@php
    $field ??= null;
    $context ??= null;

    if ($field instanceof \Monstrex\Ave\Core\Fields\Fieldset) {
        $fieldsetData = $field->toArray();

        $key = $field->getKey();
        $label = $field->getLabel();
        $required = $field->isRequired();
        $sortable = $field->isSortable();
        $collapsible = $field->isCollapsible();
        $collapsed = $field->isCollapsed();
        $minItems = $field->getMinItems();
        $maxItems = $field->getMaxItems();
        $addButtonLabel = $field->getAddButtonLabel();
        $itemInstances = $fieldsetData["itemInstances"] ?? [];
        $itemIds = $fieldsetData["itemIds"] ?? [];
        $helpText = $field->getHelpText();
        $errors = $context ? $context->getErrors($key) : [];
    }
@endphp

<div class="form-field fieldset-field @if(!empty($errors)) has-error @endif" data-field-name="{{ $key ?? 'fieldset' }}">
    <div class="fieldset-container"
         data-fieldset
         data-sortable="{{ !empty($sortable) ? 'true' : 'false' }}"
         data-collapsible="{{ !empty($collapsible) ? 'true' : 'false' }}"
         data-collapsed="{{ !empty($collapsed) ? 'true' : 'false' }}"
         data-min-items="{{ $minItems ?? '' }}"
         data-max-items="{{ $maxItems ?? '' }}"
         data-field-name="{{ $key ?? 'fieldset' }}">

        <div class="fieldset-actions">
            <div class="fieldset-actions-left">
                @if(!empty($label))
                    <label class="form-label">
                        {{ $label }}
                        @if(!empty($required))
                            <span class="required">*</span>
                        @endif
                    </label>
                @endif
            </div>

            <div class="fieldset-actions-right">
                @if(!empty($collapsible))
                    <button type="button" class="btn btn-md btn-fieldset-action" data-action="collapse-all" title="Collapse All">
                        <span>Collapse All</span>
                    </button>
                    <button type="button" class="btn btn-md btn-fieldset-action" data-action="expand-all" title="Expand All">
                        <span>Expand All</span>
                    </button>
                @endif

                @if(!empty($sortable))
                    <label class="fieldset-sort-toggle">
                        <input type="checkbox" data-action="toggle-sort">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Sort Mode</span>
                    </label>
                @endif
            </div>
        </div>

        <div class="fieldset-items" data-fieldset-items>
            @if(!empty($itemInstances))
                @foreach($itemInstances as $index => $item)
                    @php
                        $itemHasErrors = !empty($item['context']) && $item['context']->errors()->isNotEmpty();
                    @endphp
                    <div class="fieldset-item{{ !empty($collapsed) ? ' collapsed' : '' }}@if($itemHasErrors) has-nested-error @endif" data-item-index="{{ $index }}" data-item-id="{{ $item['id'] }}">
                        @if(!empty($sortable))
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
                                 data-head-title-field="{{ $field->getHeadTitle() }}"
                                 data-head-preview-field="{{ $field->getHeadPreview() }}">
                                @if(!empty($collapsible))
                                    <button type="button" class="btn-fieldset-collapse" data-action="collapse" title="Expand/Collapse">
                                        <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                                            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                @endif
                                <span class="fieldset-item-number">{{ $index + 1 }}</span>

                                @if($field->getHeadPreview())
                                    <div class="fieldset-item-preview" data-item-preview></div>
                                @endif

                                @if($field->getHeadTitle())
                                    <span class="fieldset-item-title" data-item-title></span>
                                @endif

                                <button type="button" class="btn-fieldset-delete" data-action="delete" title="Delete">
                                    <svg class="icon" width="18" height="18" viewBox="0 0 16 16" fill="none">
                                        <path d="M2 4h12M5.5 4V2.5A1.5 1.5 0 0 1 7 1h2a1.5 1.5 0 0 1 1.5 1.5V4m2 0v10a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 2.5 14V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        <path d="M6.5 7v4M9.5 7v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="fieldset-item-fields">
                                <input type="hidden" name="{{ $key }}[{{ $item['id'] }}][_id]" value="{{ $item['id'] }}" data-field-id>

                                @if(!empty($item['fields']))
                                    @foreach($item['fields'] as $itemField)
                                        @if($itemField instanceof \Monstrex\Ave\Core\Row)
                                            {{-- Render Row with processed columns --}}
                                            <div class="row">
                                                @foreach($itemField->getColumns() as $column)
                                                    <div class="col-{{ $column->getSpan() }}">
                                                        @foreach($column->getFields() as $colField)
                                                            {!! $colField->render($item['context']) !!}
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- Render regular field --}}
                                            {!! $itemField->render($item['context']) !!}
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="fieldset-actions-footer">
            <button type="button" class="btn btn-fieldset-add" data-action="add">
                <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>{{ $addButtonLabel }}</span>
            </button>
        </div>
    </div>

    @if(!empty($errors))
        <div class="error-message">
            @foreach($errors as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(!empty($helpText))
        <div class="help-text">{{ $helpText }}</div>
    @endif
</div>

{{-- Template for new items --}}
<template id="fieldset-template-{{ $key ?? 'fieldset' }}">
    @php
        $fieldsetInstance = $field; // Save fieldset instance before it gets overwritten in loops
        $templateFields = $fieldsetInstance?->prepareTemplateFields() ?? [];
    @endphp
    <div class="fieldset-item{{ !empty($collapsed) ? ' collapsed' : '' }}" data-item-index="__INDEX__">
        @if(!empty($sortable))
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
                 data-head-title-field="{{ $field->getHeadTitle() }}"
                 data-head-preview-field="{{ $field->getHeadPreview() }}">
                @if(!empty($collapsible))
                    <button type="button" class="btn-fieldset-collapse" data-action="collapse" title="Expand/Collapse">
                        <svg class="icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                @endif
                <span class="fieldset-item-number"></span>

                @if($field->getHeadPreview())
                    <div class="fieldset-item-preview" data-item-preview></div>
                @endif

                @if($field->getHeadTitle())
                    <span class="fieldset-item-title" data-item-title></span>
                @endif

                <button type="button" class="btn-fieldset-delete" data-action="delete" title="Delete">
                    <svg class="icon" width="18" height="18" viewBox="0 0 16 16" fill="none">
                        <path d="M2 4h12M5.5 4V2.5A1.5 1.5 0 0 1 7 1h2a1.5 1.5 0 0 1 1.5 1.5V4m2 0v10a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 2.5 14V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M6.5 7v4M9.5 7v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="fieldset-item-fields">
                {{-- Hidden field to store unique item ID (value will be set by JS) --}}
                <input type="hidden" name="{{ $key ?? 'fieldset' }}[__ITEM__][_id]" value="" data-field-id>

                {{-- Template fields with __ITEM__ placeholder --}}
                @if(!empty($templateFields))
                    @foreach($templateFields as $templateField)
                        @if($templateField instanceof \Monstrex\Ave\Core\Row)
                            {{-- Render Row with processed columns --}}
                            <div class="row">
                                @foreach($templateField->getColumns() as $column)
                                    <div class="col-{{ $column->getSpan() }}">
                                        @foreach($column->getFields() as $templateItemField)
                                            {!! $templateItemField->render($context) !!}
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Render regular field --}}
                            {!! $templateField->render($context) !!}
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</template>
