<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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

        foreach ($this->fieldset->getChildSchema() as $definition) {
            if (!$definition instanceof AbstractField) {
                continue;
            }

            $nestedField = $definition->nestWithin($this->fieldset->getKey(), (string) $itemId);

            // PHASE 1: Set container reference to enable state path composition
            if (method_exists($nestedField, 'container')) {
                $nestedField = $nestedField->container($this->fieldset);
            }

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
                $nestedField->prepareForDisplay(FormContext::forData($itemData));
            }

            $fields[] = $nestedField;
        }

        Log::debug('Fieldset item prepared', [
            'fieldset' => $this->fieldset->getKey(),
            'item_id' => $itemId,
            'field_keys' => array_map(
                static fn (AbstractField $field): string => $field->getKey(),
                $fields
            ),
        ]);

        return new Item($index, $itemId, $itemData, $fields);
    }

    /**
     * @return array<int,AbstractField>
     */
    public function makeTemplateFields(): array
    {
        $templateFields = [];

        foreach ($this->fieldset->getChildSchema() as $definition) {
            if (!$definition instanceof AbstractField) {
                continue;
            }

            $templateField = $definition->nestWithin($this->fieldset->getKey(), '__ITEM__');

            // PHASE 1: Mark as template and set container (enables state path composition)
            if (method_exists($templateField, 'markAsTemplate')) {
                $templateField = $templateField->markAsTemplate();
            }

            if (method_exists($templateField, 'container')) {
                $templateField = $templateField->container($this->fieldset);
            }

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
