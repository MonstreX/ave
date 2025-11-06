<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\FormContext;

/**
 * Immutable representation of a single Fieldset item prepared for rendering.
 */
final class Item
{
    /**
     * @param  array<int,AbstractField>  $fields
     */
    public function __construct(
        public readonly int $index,
        public readonly int $id,
        public readonly array $data,
        public readonly array $fields,
        public readonly FormContext $context,
    ) {}
}
