<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * Select Field
 *
 * A dropdown select field for choosing from predefined options.
 *
 * Features:
 * - Single or multiple selection
 * - Searchable/filterable options
 * - Key-value option pairs (value => display label)
 * - HTML select element
 *
 * Example:
 *   Select::make('status')
 *       ->label('Status')
 *       ->options([
 *           'active' => 'Active',
 *           'inactive' => 'Inactive',
 *           'archived' => 'Archived'
 *       ])
 *       ->required()
 *
 *   Select::make('tags')
 *       ->label('Tags')
 *       ->options($tags)
 *       ->multiple(true)
 *       ->searchable(true)
 */
class Select extends AbstractField
{
    /**
     * Available options as key => value pairs
     * Keys are values submitted, values are display labels
     */
    protected array $options = [];

    /**
     * Allow multiple selections
     */
    protected bool $multiple = false;

    /**
     * Enable client-side filtering/search of options
     */
    protected bool $searchable = true;

    /**
     * Set available options
     *
     * @param array $options Associative array of value => label pairs
     * @return static
     */
    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Enable/disable multiple selection
     *
     * @param bool $multiple Whether to allow multiple selections
     * @return static
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * Enable/disable searchable/filterable options
     *
     * @param bool $searchable Whether to show search field
     * @return static
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data with options, multiple, and searchable
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options'    => $this->options,
            'multiple'   => $this->multiple,
            'searchable' => $this->searchable,
        ]);
    }
}
