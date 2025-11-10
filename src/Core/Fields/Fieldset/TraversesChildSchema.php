<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Monstrex\Ave\Core\Fields\AbstractField;

/**
 * Trait TraversesChildSchema
 *
 * Provides a unified interface for traversing the child schema of a Fieldset.
 * This eliminates code duplication across validation, item creation, and request processing.
 *
 * The schema can contain:
 * - AbstractField instances (direct fields)
 *
 * Usage:
 *     $this->forEachChildInSchema(function(AbstractField $field) {
 *         // Process the field
 *     });
 */
trait TraversesChildSchema
{
    /**
     * Get the child schema for this fieldset
     *
     * @return array<int, AbstractField>
     */
    abstract public function getChildSchema(): array;

    /**
     * Iterate through all fields in the child schema
     *
     * Handles nested structures through ItemFactory/schema processing.
     * This method only operates on direct AbstractField items.
     *
     * @param callable(AbstractField): void $callback - Function to call for each field
     * @return void
     */
    protected function forEachChildInSchema(callable $callback): void
    {
        foreach ($this->getChildSchema() as $schemaItem) {
            // Only process direct AbstractField instances
            if ($schemaItem instanceof AbstractField) {
                $callback($schemaItem);
            }
        }
    }
}
