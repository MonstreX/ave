<?php

namespace Monstrex\Ave\Contracts;

/**
 * Interface for fields that provide validation attributes.
 *
 * This interface allows fields to expose their validation-related properties
 * in a standard way, eliminating the need for Reflection API.
 *
 * Example implementation:
 *
 * class TextInput extends AbstractField implements ProvidesValidationAttributes
 * {
 *     protected ?int $minLength = null;
 *     protected ?int $maxLength = null;
 *
 *     public function getValidationAttributes(): array
 *     {
 *         return [
 *             'min_length' => $this->minLength,
 *             'max_length' => $this->maxLength,
 *         ];
 *     }
 * }
 */
interface ProvidesValidationAttributes
{
    /**
     * Get validation attributes for this field.
     *
     * Returns an array of validation-related attributes that can be converted
     * to Laravel validation rules. Null values will be ignored.
     *
     * Common attributes:
     * - min_length: Minimum string length
     * - max_length: Maximum string length
     * - pattern: Regular expression pattern
     * - min: Minimum numeric value
     * - max: Maximum numeric value
     *
     * @return array<string,mixed> Array of validation attributes
     */
    public function getValidationAttributes(): array;
}
