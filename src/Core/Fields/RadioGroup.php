<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * RadioGroup Field
 *
 * A group of radio buttons for selecting a single value from multiple options.
 *
 * Features:
 * - Renders as custom styled radio buttons
 * - Supports unlimited number of options
 * - Can be displayed inline (horizontal) or stacked (vertical)
 * - Custom styled radio buttons with circle indicator (supports all browsers)
 * - Value is returned as string
 *
 * Example (Basic):
 *   RadioGroup::make('status')
 *       ->label('Status')
 *       ->options([
 *           'active' => 'Active',
 *           'inactive' => 'Inactive',
 *           'draft' => 'Draft'
 *       ])
 *       ->default('draft')
 *
 * Example (Inline):
 *   RadioGroup::make('gender')
 *       ->label('Gender')
 *       ->options([
 *           'male' => 'Male',
 *           'female' => 'Female',
 *           'other' => 'Other'
 *       ])
 *       ->inline()
 *
 * Example (Required):
 *   RadioGroup::make('country')
 *       ->label('Select Country')
 *       ->options([
 *           'us' => 'United States',
 *           'uk' => 'United Kingdom',
 *           'ca' => 'Canada'
 *       ])
 *       ->required()
 */
class RadioGroup extends AbstractField
{
    /**
     * Options for radio buttons (value => label)
     */
    protected array $optionsList = [];

    /**
     * Whether to display radio buttons inline (horizontal)
     */
    protected bool $displayInline = false;

    /**
     * Set options for radio buttons
     *
     * @param array $options Array of value => label pairs
     * @return static
     */
    public function options(array $options): static
    {
        $this->optionsList = $options;
        return $this;
    }

    /**
     * Get radio button options
     *
     * @return array Options array (value => label)
     */
    public function getOptions(): array
    {
        return $this->optionsList;
    }

    /**
     * Display radio buttons inline (horizontally)
     *
     * @param bool $inline Whether to display inline
     * @return static
     */
    public function inline(bool $inline = true): static
    {
        $this->displayInline = $inline;
        return $this;
    }

    /**
     * Check if radio buttons should be displayed inline
     *
     * @return bool
     */
    public function isInline(): bool
    {
        return $this->displayInline;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['options'] = $this->optionsList;
        $array['inline'] = $this->displayInline;

        return $array;
    }

    /**
     * Convert raw input value to string
     *
     * Radio buttons return the selected option value directly.
     *
     * @param mixed $raw Raw input value from form
     * @return string|null Selected value or null if not set
     */
    public function extract(mixed $raw): mixed
    {
        // Radio buttons return the selected value as-is
        // Empty string or null means no selection
        return $raw ?: null;
    }
}
