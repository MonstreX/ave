<?php

namespace Monstrex\Ave\Exceptions;

use Exception;

/**
 * Exception thrown when a field attempts to use hierarchical mode
 * with a model that doesn't support the required structure.
 *
 * Hierarchical mode requires:
 * - parent_id column (nullable integer for self-referencing)
 * - order column (integer for sorting siblings)
 */
class HierarchicalRelationException extends Exception
{
    public static function missingParentIdColumn(string $modelClass): self
    {
        return new self(
            "Model '{$modelClass}' does not have a 'parent_id' column. "
            . "Hierarchical relations require a 'parent_id' column for self-referencing."
        );
    }

    public static function missingOrderColumn(string $modelClass): self
    {
        return new self(
            "Model '{$modelClass}' does not have an 'order' column. "
            . "Hierarchical relations require an 'order' column for sorting siblings."
        );
    }

    public static function missingBothColumns(string $modelClass): self
    {
        return new self(
            "Model '{$modelClass}' is missing required columns for hierarchical relations: "
            . "'parent_id' (nullable integer) and 'order' (integer). "
            . "Please add these columns to support tree-like structures."
        );
    }
}
