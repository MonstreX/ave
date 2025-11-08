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
 * - Multiple display variants: default (modern toggle switch) and voyager (simple checkbox)
 * - Optional on/off labels for Voyager compatibility
 *
 * Example (Modern):
 *   Toggle::make('is_published')
 *       ->label('Published')
 *       ->default(false)
 *
 * Example (Voyager style):
 *   Toggle::make('is_active')
 *       ->displayAs('voyager')
 *       ->label('Active')
 *       ->on('Yes')
 *       ->off('No')
 *
 *   Toggle::make('agree_to_terms')
 *       ->label('I agree to the terms and conditions')
 *       ->required()
 */
class Toggle extends AbstractField
{
    /**
     * Label for checked state (Voyager compatibility)
     */
    protected ?string $onLabel = null;

    /**
     * Label for unchecked state (Voyager compatibility)
     */
    protected ?string $offLabel = null;

    /**
     * Set label for checked state (Voyager compatibility)
     *
     * @param string $label Label text for checked state
     * @return static
     */
    public function on(string $label): static
    {
        $this->onLabel = $label;
        return $this;
    }

    /**
     * Set label for unchecked state (Voyager compatibility)
     *
     * @param string $label Label text for unchecked state
     * @return static
     */
    public function off(string $label): static
    {
        $this->offLabel = $label;
        return $this;
    }

    /**
     * Get checked state label
     *
     * @return string|null Label for checked state
     */
    public function getOnLabel(): ?string
    {
        return $this->onLabel;
    }

    /**
     * Get unchecked state label
     *
     * @return string|null Label for unchecked state
     */
    public function getOffLabel(): ?string
    {
        return $this->offLabel;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with on/off labels for Voyager style
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Add Voyager compatibility labels if set
        if ($this->onLabel || $this->offLabel) {
            $array['options'] = (object)[
                'on' => $this->onLabel,
                'off' => $this->offLabel,
            ];
        }

        return $array;
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
