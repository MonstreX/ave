<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Contracts\HandlesNestedValue;
use Monstrex\Ave\Core\Components\ComponentContainer;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;

/**
 * Responsible for cloning and preparing child fields for Fieldset items.
 *
 * Note: Uses TraversesChildSchema trait via FieldsetField for consistent schema traversal.
 * See TraversesChildSchema trait for the unified schema traversal interface.
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
            // Handle Div and other ComponentContainer instances
            if ($definition instanceof ComponentContainer) {
                $fields[] = $this->processComponentContainer($definition, $itemStatePath, $itemData, $record);
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
     * @return array<int,AbstractField|ComponentContainer>
     */
    public function makeTemplateFields(): array
    {
        $templateFields = [];

        // Template path uses __TEMPLATE__ marker to prevent database pollution
        $templatePath = "{$this->fieldset->getStatePath()}.__TEMPLATE__";

        foreach ($this->fieldset->getChildSchema() as $definition) {
            // Handle ComponentContainer (Div, Group, etc.) in templates
            if ($definition instanceof ComponentContainer) {
                $templateFields[] = $this->processComponentContainerTemplate($definition, $templatePath);
                continue;
            }

            if (!$definition instanceof AbstractField) {
                continue;
            }

            // Process direct field with template marker
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
     * Process ComponentContainer (Div, Group, etc.) for template fields.
     *
     * Similar to processComponentContainer() but uses __TEMPLATE__ path for template marker.
     * Recursively handles both direct fields and nested components.
     *
     * @return ComponentContainer with processed template fields and nested components
     */
    private function processComponentContainerTemplate(
        ComponentContainer $container,
        string $templatePath
    ): ComponentContainer {
        $processedChildren = [];

        // Process direct fields with template marker
        foreach ($container->getFields() as $field) {
            $processedChildren[] = $this->processFieldForTemplate(
                $field,
                $templatePath
            );
        }

        // Process nested components recursively
        foreach ($container->getChildComponents() as $component) {
            $processedChildren[] = $this->processComponentContainerTemplate(
                $component,
                $templatePath
            );
        }

        // Create new component with processed children
        $newComponent = clone $container;

        if (!empty($processedChildren)) {
            $newComponent->schema($processedChildren);
        }

        return $newComponent;
    }

    /**
     * Process a single field for template rendering within a component container context.
     *
     * Applies template state path and marks field as template.
     */
    private function processFieldForTemplate(
        FormField $field,
        string $templatePath
    ): FormField {
        if (!$field instanceof AbstractField) {
            return $field;
        }

        $childTemplatePath = "{$templatePath}.{$field->baseKey()}";
        $templateField = $field
            ->statePath($childTemplatePath)
            ->markAsTemplate()
            ->container($this->fieldset);

        // Keep HTML key for form rendering
        $templateField = $templateField->nestWithin($this->fieldset->getKey(), '__ITEM__');

        return $templateField;
    }

    /**
     * Process ComponentContainer (Div, Group, etc.) by extracting and processing all nested fields.
     *
     * Recursively handles:
     * - Direct child fields (process each with state path)
     * - Nested child components (process recursively)
     * - Returns new component with processed children
     *
     * @return ComponentContainer with processed fields and nested components
     */
    private function processComponentContainer(
        ComponentContainer $container,
        string $itemStatePath,
        array &$itemData,
        ?Model $record
    ): ComponentContainer {
        // Collect all processed children (fields + components)
        $processedChildren = [];

        // Process direct fields
        foreach ($container->getFields() as $field) {
            $processedChildren[] = $this->processFieldForContainer(
                $field,
                $itemStatePath,
                $itemData,
                $record
            );
        }

        // Process nested components recursively
        foreach ($container->getChildComponents() as $component) {
            $processedChildren[] = $this->processComponentContainer(
                $component,
                $itemStatePath,
                $itemData,
                $record
            );
        }

        // Create new component with processed children
        // Clone the container to preserve all its configuration
        $newComponent = clone $container;

        // schema() method of HasComponents trait handles both fields and components
        if (!empty($processedChildren)) {
            $newComponent->schema($processedChildren);
        }

        return $newComponent;
    }

    /**
     * Process a single field within a component container context.
     *
     * Applies state path, data loading, and display preparation.
     */
    private function processFieldForContainer(
        FormField $field,
        string $itemStatePath,
        array &$itemData,
        ?Model $record
    ): FormField {
        if (!$field instanceof AbstractField) {
            return $field;
        }

        $childStatePath = "{$itemStatePath}.{$field->baseKey()}";
        $nestedField = $field
            ->statePath($childStatePath)
            ->container($this->fieldset);

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

        return $nestedField;
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
