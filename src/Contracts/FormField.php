<?php

namespace Monstrex\Ave\Contracts;

/**
 * FormField Contract
 * Defines the interface for form fields
 * NOTE: Will be fully implemented in PHASE-2
 */
interface FormField
{
    /**
     * Get field key (database column name)
     *
     * @return string
     */
    public function key(): string;

    /**
     * Get field label
     *
     * @return string
     */
    public function label(): string;

    /**
     * Check if field is required
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Convert field to array representation
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Extract value from raw data
     *
     * @param mixed $raw Raw value
     * @return mixed Extracted value
     */
    public function extract(mixed $raw): mixed;
}
