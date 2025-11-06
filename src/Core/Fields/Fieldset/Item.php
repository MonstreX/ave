<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Row;

/**
 * Immutable representation of a single Fieldset item prepared for rendering.
 *
 * Fields array can contain:
 * - AbstractField instances (TextInput, Textarea, etc.)
 * - Row instances with Col containers (for grid-based layout)
 */
final class Item
{
    /**
     * @param  array<int,AbstractField|Row>  $fields
     */
    public function __construct(
        public readonly int $index,
        public readonly int $id,
        public readonly array $data,
        public readonly array $fields,
        public readonly FormContext $context,
    ) {}
}
