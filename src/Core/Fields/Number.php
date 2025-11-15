<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Contracts\ProvidesValidationAttributes;

/**
 * Number Field
 *
 * An input field for numeric values (integers and floats).
 *
 * Features:
 * - Minimum and maximum value constraints
 * - Configurable step increment
 * - Automatic type casting to float
 * - HTML input type: number
 *
 * Example:
 *   Number::make('age')
 *       ->label('Age')
 *       ->min(0)
 *       ->max(150)
 *       ->step(1)
 *       ->required()
 */
class Number extends AbstractField implements ProvidesValidationAttributes
{
    /**
     * Minimum allowed value
     */
    protected ?float $min = null;

    /**
     * Maximum allowed value
     */
    protected ?float $max = null;

    /**
     * Step increment for spin buttons
     */
    protected ?float $step = null;

    /**
     * Set minimum allowed value
     *
     * @param float $min Minimum value allowed
     * @return static
     */
    public function min(float $min): static
    {
        $this->min = $min;
        return $this;
    }

    /**
     * Set maximum allowed value
     *
     * @param float $max Maximum value allowed
     * @return static
     */
    public function max(float $max): static
    {
        $this->max = $max;
        return $this;
    }

    /**
     * Set step increment for spin buttons
     *
     * @param float $step Increment step value
     * @return static
     */
    public function step(float $step): static
    {
        $this->step = $step;
        return $this;
    }

    /**
     * Get validation attributes for this field.
     *
     * Returns validation-related properties that can be converted to Laravel rules
     * by FieldValidationRuleExtractor.
     *
     * @return array<string,mixed>
     */
    public function getValidationAttributes(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with min, max, and step
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'min'  => $this->min,
            'max'  => $this->max,
            'step' => $this->step,
        ]);
    }

    /**
     * Convert raw input value to float
     *
     * Extracts numeric value from raw input, returning null for empty/null values
     * and casting all other values to float.
     *
     * @param mixed $raw Raw input value
     * @return float|null Converted float or null if input is empty
     */
    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return (float) $raw;
    }
}
