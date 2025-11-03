<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Closure;

/**
 * Result of processing Fieldset request payload.
 */
final class ProcessResult
{
    /**
     * @param  array<int,array<string,mixed>>  $items
     * @param  array<int,Closure>  $deferredActions
     */
    public function __construct(
        private array $items,
        private array $deferredActions,
    ) {}

    /**
     * @return array<int,array<string,mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return array<int,Closure>
     */
    public function deferredActions(): array
    {
        return $this->deferredActions;
    }
}

