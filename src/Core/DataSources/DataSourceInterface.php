<?php

namespace Monstrex\Ave\Core\DataSources;

/**
 * Interface for data sources that can be used to populate form fields
 *
 * This abstraction allows fields to work with different data sources:
 * - ModelDataSource: for Eloquent models (current behavior)
 * - ArrayDataSource: for arrays and JSON structures (new)
 *
 * Enables universal field handling across different data contexts
 * (especially important for FieldSet with nested JSON data)
 */
interface DataSourceInterface
{
    /**
     * Get a value from the data source using dot notation
     *
     * @param  string  $key  Key in dot notation (e.g., 'title' or 'items.0.name')
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Set a value in the data source using dot notation
     *
     * @param  string  $key  Key in dot notation
     * @param  mixed  $value  Value to set
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a key exists in the data source
     *
     * @param  string  $key  Key in dot notation
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get all data as an array
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Sync a relation (for BelongsToMany relations)
     *
     * @param  string  $relation  Relation name
     * @param  array  $ids  Array of related model IDs
     * @return void
     */
    public function sync(string $relation, array $ids): void;
}
