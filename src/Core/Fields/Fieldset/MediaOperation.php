<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

/**
 * Represents a queued media operation for a Fieldset item.
 */
final class MediaOperation
{
    /**
     * @param  array<int,int>  $uploaded
     * @param  array<int,int>  $deleted
     * @param  array<int,int>  $order
     * @param  array<int,array<string,mixed>>  $props
     */
    public function __construct(
        public readonly string $collection,
        public readonly array $uploaded,
        public readonly array $deleted,
        public readonly array $order,
        public readonly array $props,
    ) {}

    public function hasAny(): bool
    {
        return !empty($this->uploaded)
            || !empty($this->deleted)
            || !empty($this->order)
            || !empty($this->props);
    }
}

