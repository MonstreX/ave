<?php

namespace Monstrex\Ave\Core\Fields\Concerns;

use Closure;

/**
 * Trait for shared query modifier methods used by relation fields.
 *
 * Eliminates code duplication between BelongsToSelect and BelongsToManySelect.
 * Provides methods for modifying the options query (where, orderBy, etc.)
 */
trait HasRelationQueryModifiers
{
    /** Mark field as searchable (for async loading or client-side). */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        $this->clearOptionsCache();
        return $this;
    }

    /** Set options limit for eager-loaded options. */
    public function optionsLimit(int $limit): static
    {
        $this->optionsLimit = max(1, $limit);
        $this->clearOptionsCache();
        return $this;
    }

    /**
     * Modify the underlying options query.
     *
     * @param Closure $callback fn(Builder $query): Builder
     */
    public function modifyQuery(Closure $callback): static
    {
        $this->modifyQuery = $callback;
        $this->clearOptionsCache();
        return $this;
    }

    /**
     * Add WHERE condition to options query.
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value (if only 2 args passed)
     * @param mixed $value Value (optional)
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        $previousCallback = $this->modifyQuery;

        $this->modifyQuery = function ($query) use ($column, $operator, $value, $previousCallback) {
            if ($previousCallback) {
                $query = $previousCallback($query);
            }

            // Handle different argument combinations
            if ($value === null && $operator !== null) {
                // Two arguments: where('status', true)
                return $query->where($column, $operator);
            }

            // Three arguments: where('status', '=', true)
            return $query->where($column, $operator, $value);
        };

        $this->clearOptionsCache();
        return $this;
    }

    /**
     * Add ORDER BY to options query.
     *
     * @param string $column Column name
     * @param string $direction 'asc' or 'desc'
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $previousCallback = $this->modifyQuery;

        $this->modifyQuery = function ($query) use ($column, $direction, $previousCallback) {
            if ($previousCallback) {
                $query = $previousCallback($query);
            }

            return $query->orderBy($column, $direction);
        };

        $this->clearOptionsCache();
        return $this;
    }

    /**
     * Filter for 'active' records (where status = true).
     * Assumes 'status' column exists on related model.
     */
    public function active(): static
    {
        return $this->where('status', true);
    }

    /**
     * Filter for 'inactive' records (where status = false).
     */
    public function inactive(): static
    {
        return $this->where('status', false);
    }

    /** Clear the cached options when query is modified. */
    protected function clearOptionsCache(): void
    {
        $this->optionsCache = null;
    }
}
