<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;
use Monstrex\Ave\Core\Fields\Media;

/**
 * Responsible for cloning and preparing child fields for Fieldset items.
 */
class ItemFactory
{
    public function __construct(
        private FieldsetField $fieldset,
    ) {}

    public function makeFromData(int $index, array $itemData, ?Model $record): Item
    {
        $itemId = $this->resolveItemId($itemData, $index);
        $fields = [];

        foreach ($this->fieldset->getChildSchema() as $definition) {
            if (!$definition instanceof AbstractField) {
                continue;
            }

            $fieldClone = clone $definition;
            $originalKey = $definition->getKey();

            $this->fillField($fieldClone, $itemData, $record, $originalKey);
            $this->renameField($fieldClone, $originalKey, $index, $itemId);

            $fields[] = $fieldClone;
        }

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

            $clone = clone $definition;
            $reflection = new \ReflectionClass($clone);
            $property = $reflection->getProperty('key');
            $property->setAccessible(true);

            $originalName = $property->getValue($clone);
            $templateName = sprintf('%s[__INDEX__][%s]', $this->fieldset->getKey(), $originalName);
            $property->setValue($clone, $templateName);

            if ($clone instanceof Media) {
                $overrideProperty = $reflection->getProperty('collectionNameOverride');
                $overrideProperty->setAccessible(true);
                $overrideProperty->setValue(
                    $clone,
                    sprintf('%s___INDEX___%s', $this->fieldset->getKey(), $originalName)
                );
            }

            $templateFields[] = $clone;
        }

        return $templateFields;
    }

    private function resolveItemId(array &$itemData, int $index): int
    {
        $itemData['_id'] = isset($itemData['_id']) ? (int) $itemData['_id'] : ($index + 1);

        return $itemData['_id'];
    }

    private function fillField(AbstractField $field, array $itemData, ?Model $record, string $originalKey): void
    {
        $dataSource = new ArrayDataSource($itemData);
        $field->fillFromDataSource($dataSource);

        if ($field instanceof Media && $record) {
            $collectionName = $itemData[$originalKey] ?? null;

            if (is_string($collectionName) && $collectionName !== '') {
                $field->setCollectionNameOverride($collectionName);
                if (method_exists($field, 'fillFromCollectionName')) {
                    $field->fillFromCollectionName($record, $collectionName);
                }
            }
        }

        if (!($field instanceof Media) && method_exists($field, 'prepareForDisplay')) {
            $itemContextData = $itemData;
            $field->prepareForDisplay(FormContext::forData($itemContextData));
        }
    }

    private function renameField(AbstractField $field, string $originalKey, int $index, int $itemId): void
    {
        $reflection = new \ReflectionClass($field);
        $property = $reflection->getProperty('key');
        $property->setAccessible(true);

        if ($field instanceof Media) {
            $originalNameProp = $reflection->getProperty('originalName');
            $originalNameProp->setAccessible(true);
            $originalNameProp->setValue($field, $originalKey);

            $field->setFieldSetItemId($itemId);
        }

        $property->setValue(
            $field,
            sprintf('%s[%d][%s]', $this->fieldset->getKey(), $index, $originalKey)
        );
    }
}
