<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Monstrex\Ave\Contracts\HandlesNestedValue;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;

/**
 * Responsible for cloning and preparing child fields for Fieldset items.
 */
class ItemFactory
{
    public function __construct(
        private FieldsetField $fieldset,
    ) {
    }

    public function makeFromData(int $index, array &$itemData, ?Model $record, ?FormContext $parentContext = null): Item
    {
        $itemId = $this->resolveItemId($itemData, $index);
        $fields = [];

        // Build the state path for this item (e.g., 'items.0', 'items.1', etc.)
        $itemStatePath = $this->fieldset->getItemStatePath($itemId);

        foreach ($this->fieldset->getChildSchema() as $definition) {
            // Handle Row/Col containers
            if ($definition instanceof Row) {
                $fields[] = $this->processRow($definition, $itemStatePath, $itemData, $record);
                continue;
            }

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

        // Transfer errors from parent context to item context
        // Errors are keyed like "fieldset.*.title" for fieldset items, so we need to extract
        // errors for this specific item index
        if ($parentContext) {
            $this->transferItemErrors($itemContext, $parentContext, $itemStatePath, $this->fieldset->getKey());
        }

        return new Item($index, $itemId, $itemData, $fields, $itemContext);
    }

    /**
     * Process Row/Col structure: flatten fields while preserving layout.
     *
     * When Fieldset schema contains Row with Cols, we need to:
     * 1. Extract all fields from all columns
     * 2. Process each field (state path, data loading, etc.)
     * 3. Store fields as flat list (layout is for display, not data structure)
     *
     * @return Row with processed Col/Field structure
     */
    private function processRow(Row $row, string $itemStatePath, array &$itemData, ?Model $record): Row
    {
        $processedColumns = [];

        foreach ($row->getColumns() as $col) {
            $processedFields = [];

            foreach ($col->getFields() as $field) {
                // Only process AbstractField instances
                if (!$field instanceof AbstractField) {
                    $processedFields[] = $field;
                    continue;
                }

                // Use same processing logic as regular fields
                $childStatePath = "{$itemStatePath}.{$field->baseKey()}";
                $nestedField = $field
                    ->statePath($childStatePath)
                    ->container($this->fieldset);

                // Keep HTML key for form rendering
                $nestedField = $nestedField->nestWithin($this->fieldset->getKey(), (string) $itemData['_id'] ?? 0);

                $baseKey = $field->baseKey();
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
                    $originalKey = $nestedField->getKey();
                    $nestedField->setKey($baseKey);
                    $nestedField->prepareForDisplay(FormContext::forData($itemData));
                    $nestedField->setKey($originalKey);
                }

                $processedFields[] = $nestedField;
            }

            // Create new Col with processed fields
            $processedCol = Col::make($col->getSpan())->fields($processedFields);
            $processedColumns[] = $processedCol;
        }

        // Return new Row with processed columns
        return Row::make()->columns($processedColumns);
    }

    /**
     * @return array<int,AbstractField|Row>
     */
    public function makeTemplateFields(): array
    {
        $templateFields = [];

        // Template path uses __TEMPLATE__ marker to prevent database pollution
        $templatePath = "{$this->fieldset->getStatePath()}.__TEMPLATE__";

        foreach ($this->fieldset->getChildSchema() as $definition) {
            // Handle Row/Col containers in templates
            if ($definition instanceof Row) {
                $templateFields[] = $this->processTemplateRow($definition, $templatePath);
                continue;
            }

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

    /**
     * Process Row/Col structure for template fields.
     *
     * Similar to processRow() but uses __TEMPLATE__ path for template marker.
     */
    private function processTemplateRow(Row $row, string $templatePath): Row
    {
        $processedColumns = [];

        foreach ($row->getColumns() as $col) {
            $processedFields = [];

            foreach ($col->getFields() as $field) {
                // Only process AbstractField instances
                if (!$field instanceof AbstractField) {
                    $processedFields[] = $field;
                    continue;
                }

                // Use template path with __TEMPLATE__ marker
                $childTemplatePath = "{$templatePath}.{$field->baseKey()}";
                $templateField = $field
                    ->statePath($childTemplatePath)
                    ->markAsTemplate()
                    ->container($this->fieldset);

                // Keep HTML key for form rendering
                $templateField = $templateField->nestWithin($this->fieldset->getKey(), '__ITEM__');

                $processedFields[] = $templateField;
            }

            // Create new Col with processed fields
            $processedCol = Col::make($col->getSpan())->fields($processedFields);
            $processedColumns[] = $processedCol;
        }

        // Return new Row with processed columns
        return Row::make()->columns($processedColumns);
    }

    /**
     * Transfer validation errors from parent context to item context.
     *
     * Validation errors come from Laravel as 'fieldset_key.INDEX.field_key'
     * We need to extract errors for this specific item and make them available to the field's key() method.
     *
     * @param FormContext $itemContext The context for this specific item
     * @param FormContext $parentContext The parent form context containing all errors
     * @param string $itemStatePath The state path for this item (e.g., 'features.0')
     * @param string $fieldsetKey The fieldset field key
     * @return void
     */
    private function transferItemErrors(
        FormContext $itemContext,
        FormContext $parentContext,
        string $itemStatePath,
        string $fieldsetKey
    ): void {
        // Extract item index and ID from itemStatePath (e.g., "features.0" → index=0)
        if (!preg_match('/\.(\d+)$/', $itemStatePath, $indexMatches)) {
            return; // Can't extract index, skip error transfer
        }

        $currentItemIndex = (int) $indexMatches[1];

        // Get all errors from parent context
        $allErrors = $parentContext->errors();

        // Extract errors that belong to this specific item
        // Pattern: 'fieldset_key.INDEX.field_key'
        foreach ($allErrors->messages() as $errorKey => $messages) {
            // Format: "features.0.title", "features.1.content", etc.
            $pattern = '/^' . preg_quote($fieldsetKey) . '\.(\d+)\.(.+)$/';

            if (preg_match($pattern, $errorKey, $matches)) {
                $errorItemIndex = (int) $matches[1];
                $baseFieldKey = $matches[2];

                // Only add errors that belong to this specific item
                if ($errorItemIndex === $currentItemIndex) {
                    // Convert baseKey (title) to the HTML format key that field->key() will use
                    // field->key() returns something like "features[0][title]"
                    // So we need to construct the same format for the error key
                    $htmlFormattedKey = sprintf('%s[%d][%s]', $fieldsetKey, $currentItemIndex, $baseFieldKey);

                    foreach ($messages as $message) {
                        $itemContext->addError($htmlFormattedKey, $message);
                    }
                }
            }
        }
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
