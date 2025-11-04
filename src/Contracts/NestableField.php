<?php

namespace Monstrex\Ave\Contracts;

/**
 * Contract for fields that can be duplicated within nested containers
 * (such as Fieldset items) without the container touching internals.
 */
interface NestableField
{
    /**
     * Return a cloned instance of the field adjusted for a nested context.
     *
     * @param  string  $parentKey  Field name of the container (HTML bracket notation).
     * @param  string  $itemIdentifier  Stable identifier for the nested item.
     */
    public function nestWithin(string $parentKey, string $itemIdentifier): static;

    /**
     * Get the original key defined when the field was created.
     */
    public function baseKey(): string;
}

