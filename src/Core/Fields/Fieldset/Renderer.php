<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;

/**
 * Builds renderable Fieldset items from stored data.
 */
class Renderer
{
    public function __construct(
        private ItemFactory $itemFactory,
    ) {}

    public function render(FieldsetField $fieldset, FormContext $context): RenderResult
    {
        $record = $context->record();
        $dataSource = $context->dataSource();
        $rawItems = $dataSource ? $dataSource->get($fieldset->getKey()) : [];

        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            $rawItems = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $normalized = array_values(array_filter($rawItems, 'is_array'));
        $items = [];

        foreach ($normalized as $index => $itemData) {
            $itemData = is_array($itemData) ? $itemData : [];
            $items[] = $this->itemFactory->makeFromData($index, $itemData, $record);
        }

        $templateFields = $this->itemFactory->makeTemplateFields();

        return new RenderResult($items, $templateFields);
    }
}

