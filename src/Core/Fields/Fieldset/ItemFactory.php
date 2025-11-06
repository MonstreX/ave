<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Contracts\HandlesNestedValue;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;

/**
 * Responsible for cloning and preparing child fields for Fieldset items.
 */
class ItemFactory
{
    public function __construct(
        private FieldsetField $fieldset,
    ) {
    }

    public function makeFromData(int $index, array &$itemData, ?Model $record): Item
    {
        $itemId = $this->resolveItemId($itemData, $index);
        $fields = [];

        // Build the state path for this item (e.g., 'items.0', 'items.1', etc.)
        $itemStatePath = $this->fieldset->getItemStatePath($itemId);

        foreach ($this->fieldset->getChildSchema() as $definition) {
            if (!$definition instanceof AbstractField) {
                continue;
            }

            // PHASE 3: Use state path composition instead of HTML key building
            // Set explicit state path for child
            $childStatePath = "{$itemStatePath}.{$definition->baseKey()}";
            $nestedField = $definition
                ->statePath($childStatePath)
                ->container($this->fieldset);

            // Keep HTML key for form rendering (backward compat in templates)
            $nestedField = $nestedField->nestWithin($this->fieldset->getKey(), (string) $itemId);

            $baseKey = $definition->baseKey();
            $storedValue = $itemData[$baseKey] ?? null;

            $handledViaContract = false;

            if ($nestedField instanceof HandlesNestedValue) {
                $nestedField->applyNestedValue($storedValue, $record);
                $handledViaContract = true;
            }

            if (!$handledViaContract) {
                $dataSource = new ArrayDataSource($itemData);
                $originalKey = $nestedField->getKey();

                $nestedField->setKey($baseKey);
                $nestedField->fillFromDataSource($dataSource);
                $nestedField->setKey($originalKey);
            }

            if (method_exists($nestedField, 'prepareForDisplay')) {
                // Temporarily set key to baseKey for prepareForDisplay to work with itemData
                $originalKey = $nestedField->getKey();
                $nestedField->setKey($baseKey);
                $nestedField->prepareForDisplay(FormContext::forData($itemData));
                $nestedField->setKey($originalKey);
            }

            $fields[] = $nestedField;
        }

        // Create a context for this item with itemData as the data source
        $itemContext = FormContext::forData($itemData);

        return new Item($index, $itemId, $itemData, $fields, $itemContext);
    }

    /**
     * @return array<int,AbstractField>
     */
    public function makeTemplateFields(): array
    {
        $templateFields = [];

        // Template path uses __TEMPLATE__ marker to prevent database pollution
        $templatePath = "{$this->fieldset->getStatePath()}.__TEMPLATE__";

        foreach ($this->fieldset->getChildSchema() as $definition) {
            if (!$definition instanceof AbstractField) {
                continue;
            }

            // PHASE 3: Use state path with template marker
            $childTemplatePath = "{$templatePath}.{$definition->baseKey()}";
            $templateField = $definition
                ->statePath($childTemplatePath)
                ->markAsTemplate()
                ->container($this->fieldset);

            // Keep HTML key for form rendering (backward compat in templates)
            $templateField = $templateField->nestWithin($this->fieldset->getKey(), '__ITEM__');

            $templateFields[] = $templateField;
        }

        return $templateFields;
    }

    private function resolveItemId(array &$itemData, int $fallbackIndex): int
    {
        $identifier = $itemData['_id'] ?? null;

        if (is_numeric($identifier)) {
            $id = (int) $identifier;
        } elseif (is_string($identifier) && ctype_digit($identifier)) {
            $id = (int) $identifier;
        } else {
            $id = $fallbackIndex;
        }

        $itemData['_id'] = $id;

        return $id;
    }
}
