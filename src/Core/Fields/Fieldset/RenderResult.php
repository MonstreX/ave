<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

/**
 * Value object returned by the Fieldset renderer.
 */
final class RenderResult
{
    /**
     * @param  array<int,Item>  $items
     * @param  array<int,mixed>  $templateFields
     */
    public function __construct(
        private array $items,
        private array $templateFields,
    ) {}

    /**
     * @return array<int,Item>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return array<int,mixed>
     */
    public function templateFields(): array
    {
        return $this->templateFields;
    }

    /**
     * @return array<int,int>
     */
    public function itemIds(): array
    {
        return array_map(static fn (Item $item): int => $item->id, $this->items);
    }
}
