<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * CheckboxGroup Field
 *
 * A group of checkboxes for selecting multiple values from a list of options.
 *
 * Features:
 * - Multiple checkbox selection
 * - Custom styled checkboxes
 * - Can be displayed inline (horizontal) or stacked (vertical)
 * - Support for default selected values
 * - Returns array of selected values
 *
 * Example (Vertical):
 *   CheckboxGroup::make('permissions')
 *       ->label('Permissions')
 *       ->options([
 *           'create' => 'Create',
 *           'read' => 'Read',
 *           'update' => 'Update',
 *           'delete' => 'Delete'
 *       ])
 *       ->default(['read'])
 *
 * Example (Inline):
 *   CheckboxGroup::make('features')
 *       ->label('Features')
 *       ->options([
 *           'notifications' => 'Notifications',
 *           'analytics' => 'Analytics',
 *           'api' => 'API Access'
 *       ])
 *       ->inline()
 *       ->default(['notifications', 'analytics'])
 */
class CheckboxGroup extends AbstractField
{
    /**
     * Options for checkboxes (value => label)
     */
    protected array $optionsList = [];

    /**
     * Whether to display checkboxes inline (horizontal)
     */
    protected bool $displayInline = false;

    /**
     * Set options for checkboxes
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
     * Get checkbox options
     *
     * @return array Options array (value => label)
     */
    public function getOptions(): array
    {
        return $this->optionsList;
    }

    /**
     * Display checkboxes inline (horizontally)
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
     * Check if checkboxes should be displayed inline
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
     * Convert raw input values to array
     *
     * Checkbox groups return array of selected values.
     *
     * @param mixed $raw Raw input value from form
     * @return array|null Array of selected values or null if none selected
     */
    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // If already an array, return it
        if (is_array($raw)) {
            return !empty($raw) ? $raw : null;
        }

        // If string, wrap in array
        return [$raw];
    }
}
