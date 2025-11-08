<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * Toggle Field
 *
 * A checkbox/toggle input for boolean yes/no values.
 *
 * Features:
 * - Automatic conversion to boolean values
 * - Supports 'on', '1', 1, true for true values
 * - All other values converted to false
 * - HTML input type: checkbox
 *
 * Example:
 *   Toggle::make('is_published')
 *       ->label('Published')
 *       ->default(false)
 *
 *   Toggle::make('agree_to_terms')
 *       ->label('I agree to the terms and conditions')
 *       ->required()
 */
class Toggle extends AbstractField
{
    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        return parent::toArray();
    }

    /**
     * Convert raw input value to boolean
     *
     * Converts checkbox/toggle input values to boolean:
     * - 'on', '1', 1, true → true
     * - All other values → false
     *
     * @param mixed $raw Raw input value from form
     * @return bool Converted boolean value
     */
    public function extract(mixed $raw): mixed
    {
        // Convert checkbox value to boolean
        if ($raw === 'on' || $raw === '1' || $raw === 1 || $raw === true) {
            return true;
        }
        return false;
    }
}
