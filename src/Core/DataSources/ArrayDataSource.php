<?php

namespace Monstrex\Ave\Core\DataSources;

use Illuminate\Support\Arr;

/**
 * Data source for working with arrays and JSON structures
 *
 * This class enables form fields to work with array/JSON data instead of models.
 * Used for:
 * - FieldSet (repeater) items
 * - Popup forms (editing media props, etc.)
 * - Any scenario where data comes from JSON instead of direct model attributes
 *
 * Critical for nested field handling in FieldSet where each item's data
 * is stored as JSON and needs to be accessed uniformly via DataSource interface.
 */
class ArrayDataSource implements DataSourceInterface
{
    /**
     * @param  array  $data  Reference to the data array (allows modification)
     */
    public function __construct(
        private array &$data
    ) {}

    /**
     * Get value from array using dot notation
     *
     * Supports nested access like 'items.0.title'
     *
     * @param  string  $key  Key in dot notation
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return data_get($this->data, $key);
    }

    /**
     * Set value in array using dot notation
     *
     * Supports nested setting like 'items.0.title' = 'value'
     * Creates nested arrays if they don't exist
     *
     * @param  string  $key  Key in dot notation
     * @param  mixed  $value  Value to set
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Check if key exists in array
     *
     * @param  string  $key  Key in dot notation
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Get all data as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get reference to the underlying data array
     *
     * Useful for direct manipulation when needed
     *
     * @return array
     */
    public function &getData(): array
    {
        return $this->data;
    }

    /**
     * Sync a relation (not supported for ArrayDataSource)
     *
     * @param  string  $relation  Relation name
     * @param  array  $ids  Array of related model IDs
     * @return void
     */
    public function sync(string $relation, array $ids): void
    {
        // ArrayDataSource doesn't support model relations
        // This is a no-op to satisfy the interface
    }
}
