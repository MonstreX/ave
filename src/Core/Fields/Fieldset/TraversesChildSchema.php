<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Row;

/**
 * Trait TraversesChildSchema
 *
 * Provides a unified interface for traversing the child schema of a Fieldset.
 * This eliminates code duplication across validation, item creation, and request processing.
 *
 * The schema can contain:
 * - AbstractField instances (direct fields)
 * - Row instances (containing columns with fields)
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
     * @return array<int, AbstractField|Row>
     */
    abstract public function getChildSchema(): array;

    /**
     * Iterate through all fields in the child schema
     *
     * Handles nested structures:
     * - Row -> Column -> Field
     * - Direct Field
     *
     * @param callable(AbstractField): void $callback - Function to call for each field
     * @return void
     */
    protected function forEachChildInSchema(callable $callback): void
    {
        foreach ($this->getChildSchema() as $schemaItem) {
            // Handle Row containers - iterate through columns and their fields
            if ($schemaItem instanceof Row) {
                foreach ($schemaItem->getColumns() as $column) {
                    foreach ($column->getFields() as $field) {
                        if ($field instanceof AbstractField) {
                            $callback($field);
                        }
                    }
                }
                continue;
            }

            // Handle direct AbstractField
            if ($schemaItem instanceof AbstractField) {
                $callback($schemaItem);
            }
        }
    }
}
