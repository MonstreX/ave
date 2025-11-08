<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * Checkbox Field
 *
 * A single checkbox input for boolean yes/no values.
 *
 * Features:
 * - Renders as a single checkbox with custom styled checkbox
 * - Automatic conversion to boolean values (1/0)
 * - Supports 'on', '1', 1, true for true values
 * - All other values converted to false
 * - Optional text label next to the checkbox
 * - Custom styled checkbox with checkmark (supports all browsers)
 *
 * Example (Basic):
 *   Checkbox::make('is_published')
 *       ->label('Published')
 *       ->default(false)
 *
 * Example (With checkbox label):
 *   Checkbox::make('agree_to_terms')
 *       ->label('Confirmation')
 *       ->checkboxLabel('I agree to the terms and conditions')
 *
 * Example (Required):
 *   Checkbox::make('accept_license')
 *       ->label('License Agreement')
 *       ->checkboxLabel('I accept the license')
 *       ->required()
 */
class Checkbox extends AbstractField
{
    /**
     * Label text shown next to the checkbox
     */
    protected ?string $checkboxLabelText = null;

    /**
     * Set text label shown next to the checkbox
     *
     * @param string $label Label text
     * @return static
     */
    public function checkboxLabel(string $label): static
    {
        $this->checkboxLabelText = $label;
        return $this;
    }

    /**
     * Get checkbox label text
     *
     * @return string|null Label text for checkbox
     */
    public function getCheckboxLabel(): ?string
    {
        return $this->checkboxLabelText;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['checkboxLabel'] = $this->checkboxLabelText;

        return $array;
    }

    /**
     * Convert raw input value to boolean
     *
     * Converts checkbox input values to boolean:
     * - 'on', '1', 1, true → 1 (stored as database truthy value)
     * - All other values → 0 (stored as database falsy value)
     *
     * @param mixed $raw Raw input value from form
     * @return int Binary value (0 or 1)
     */
    public function extract(mixed $raw): mixed
    {
        // Convert checkbox value to 0 or 1 for database storage
        if ($raw === 'on' || $raw === '1' || $raw === 1 || $raw === true) {
            return 1;
        }
        return 0;
    }
}
