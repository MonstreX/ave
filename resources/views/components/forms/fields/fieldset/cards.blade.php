@php
    $field ??= null;
    $context ??= null;

    if ($field instanceof \Monstrex\Ave\Core\Fields\Fieldset) {
        $fieldsetData = $field->toArray();

        $key = $field->getKey();
        $label = $field->getLabel();
        $required = $field->isRequired();
        $columns = $field->getColumns();
        $addButtonLabel = $field->getAddButtonLabel();
        $itemInstances = $fieldsetData["itemInstances"] ?? [];
        $itemIds = $fieldsetData["itemIds"] ?? [];
        $helpText = $field->getHelpText();
        $errors = $context ? $context->getErrors($key) : [];
    }
@endphp

<div class="form-field fieldset-cards @if(!empty($errors)) has-error @endif" data-field-name="{{ $key ?? 'fieldset' }}">
    @if(!empty($label))
        <label class="form-label">
            {{ $label }}
            @if(!empty($required))
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <div class="fieldset-cards-container"
         data-fieldset-cards
         style="--fieldset-columns: {{ $columns ?? 3 }};">

        {{-- Cards Grid (like media-items-grid) --}}
        <div class="fieldset-items-grid" data-cards-grid>
            @if(!empty($itemInstances))
                @foreach($itemInstances as $index => $item)
                    <div class="fieldset-item" data-card-index="{{ $index }}" data-item-id="{{ $item['id'] }}">
                        {{-- Order Badge (like media-order) --}}
                        <div class="fieldset-order">
                            {{ $index + 1 }}
                        </div>

                        {{-- Preview (like media-preview) --}}
                        <div class="fieldset-preview">
                            @if($field->getHeadPreview())
                                {{-- Try to render preview image --}}
                                @php
                                    $previewField = $field->getHeadPreview();
                                    $previewValue = $item['data'][$previewField] ?? null;
                                    $previewUrl = null;

                                    // Handle media field - get URL
                                    if ($previewValue) {
                                        if (is_array($previewValue)) {
                                            $previewUrl = $previewValue[0] ?? null;
                                        } else {
                                            $previewUrl = $previewValue;
                                        }
                                    }
                                @endphp

                                @if($previewUrl)
                                    <div class="preview-image" data-preview style="background-image: url('{{ $previewUrl }}'); background-size: cover; background-position: center;"></div>
                                @else
                                    <div class="preview-placeholder">
                                        <svg class="icon"><use href="#image"></use></svg>
                                    </div>
                                @endif
                            @else
                                <div class="preview-placeholder">
                                    <svg class="icon"><use href="#file"></use></svg>
                                </div>
                            @endif

                            {{-- Action Buttons (like media-action-holder) --}}
                            <div class="fieldset-action-holder">
                                <button type="button" class="fieldset-action" data-action="edit" title="Edit">
                                    <svg class="icon"><use href="#edit"></use></svg>
                                </button>
                                <button type="button" class="fieldset-action" data-action="delete" title="Delete">
                                    <svg class="icon"><use href="#delete"></use></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Footer with Title (like media-item-footer) --}}
                        @php
                            $itemTitle = 'Item ' . ($index + 1);
                            $titleField = $field->getHeadTitle();
                            if ($titleField && isset($item['data'][$titleField])) {
                                $itemTitle = $item['data'][$titleField];
                            }
                        @endphp
                        <div class="fieldset-item-footer">
                            <div class="fieldset-title" data-title>
                                {{ $itemTitle }}
                            </div>
                        </div>

                        {{-- Hidden Form for Sidebar --}}
                        <div class="fieldset-item-form" data-card-form>
                            <div class="form-sidebar-header">
                                <h3>Edit Item {{ $index + 1 }}</h3>
                                <button type="button" class="btn-close" data-action="close">×</button>
                            </div>
                            <div class="form-sidebar-body">
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

        {{-- Add Item Button --}}
        <button type="button" class="btn btn-primary" data-action="add">
            <svg class="icon"><use href="#plus"></use></svg> {{ $addButtonLabel }}
        </button>
    </div>

    {{-- Overlay for Sidebar --}}
    <div class="fieldset-overlay" data-overlay></div>

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
<template id="fieldset-card-template-{{ $key ?? 'fieldset' }}">
    @php
        $fieldsetInstance = $field;
        $templateFields = $fieldsetInstance?->prepareTemplateFields() ?? [];
    @endphp
    <div class="fieldset-item" data-card-index="__INDEX__">
        {{-- Order Badge --}}
        <div class="fieldset-order">__NUMBER__</div>

        {{-- Preview --}}
        <div class="fieldset-preview">
            @if($field->getHeadPreview())
                <div class="preview-placeholder">
                    <svg class="icon"><use href="#image"></use></svg>
                </div>
            @else
                <div class="preview-placeholder">
                    <svg class="icon"><use href="#file"></use></svg>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="fieldset-action-holder">
                <button type="button" class="fieldset-action" data-action="edit" title="Edit">
                    <svg class="icon"><use href="#edit"></use></svg>
                </button>
                <button type="button" class="fieldset-action" data-action="delete" title="Delete">
                    <svg class="icon"><use href="#delete"></use></svg>
                </button>
            </div>
        </div>

        {{-- Footer --}}
        <div class="fieldset-item-footer">
            <div class="fieldset-title" data-title>Item __NUMBER__</div>
        </div>

        {{-- Hidden Form for Sidebar --}}
        <div class="fieldset-item-form" data-card-form>
            <div class="form-sidebar-header">
                <h3>Edit Item __NUMBER__</h3>
                <button type="button" class="btn-close" data-action="close">×</button>
            </div>
            <div class="form-sidebar-body">
                <input type="hidden" name="{{ $key ?? 'fieldset' }}[__ITEM__][_id]" value="" data-field-id>

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
