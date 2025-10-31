<?php

namespace Monstrex\Ave\Core;

use Illuminate\Database\Eloquent\Builder;

class Table
{
    /** @var array */
    protected array $columns = [];

    /** @var array */
    protected array $filters = [];

    /** @var array */
    protected array $actions = [];

    /** @var array */
    protected array $bulkActions = [];

    protected ?array $defaultSort = null;
    protected int $perPage = 25;
    protected bool $searchable = true;
    protected ?string $searchPlaceholder = null;

    public static function make(): static
    {
        return new static();
    }

    /**
     * Define table columns
     *
     * @param array $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add single column
     */
    public function addColumn($column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Define filters
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Define row actions
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    /**
     * Define bulk actions (for selected rows)
     */
    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;
        return $this;
    }

    /**
     * Set default sort column and direction
     */
    public function defaultSort(string $column, string $direction = 'desc'): static
    {
        $this->defaultSort = [$column, $direction];
        return $this;
    }

    /**
     * Set pagination size
     */
    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * Enable/disable global search
     */
    public function searchable(bool $on = true): static
    {
        $this->searchable = $on;
        return $this;
    }

    /**
     * Set search placeholder text
     */
    public function searchPlaceholder(string $text): static
    {
        $this->searchPlaceholder = $text;
        return $this;
    }

    /**
     * Get table configuration as array
     */
    public function get(): array
    {
        return [
            'columns'           => $this->columns,
            'filters'           => $this->filters,
            'actions'           => $this->actions,
            'bulkActions'       => $this->bulkActions,
            'defaultSort'       => $this->defaultSort,
            'perPage'           => $this->perPage,
            'searchable'        => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder ?? 'Search...',
        ];
    }

    /**
     * Get all columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Apply filters to query based on request
     */
    public function applyFilters(Builder $query, array $filterValues): Builder
    {
        foreach ($this->filters as $filter) {
            if (isset($filterValues[$filter->key()])) {
                $filter->apply($query, $filterValues[$filter->key()]);
            }
        }

        return $query;
    }

    /**
     * Apply search to query
     */
    public function applySearch(Builder $query, string $searchTerm): Builder
    {
        if (!$this->searchable || trim($searchTerm) === '') {
            return $query;
        }

        $searchableColumns = array_filter($this->columns, fn($c) => $c->isSearchable());

        if (empty($searchableColumns)) {
            return $query;
        }

        return $query->where(function ($q) use ($searchableColumns, $searchTerm) {
            foreach ($searchableColumns as $column) {
                $q->orWhere($column->key(), 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Get table filters
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get row actions
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get bulk actions
     */
    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    /**
     * Get searchable columns
     */
    public function getSearchable(): array
    {
        return array_map(fn($c) => $c->key(), array_filter($this->columns, fn($c) => $c->isSearchable()));
    }

    /**
     * Get pagination per page size
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
