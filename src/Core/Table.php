<?php

namespace Monstrex\\Ave\\Core;

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
     * Define table columns.
     *
     * @param array $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function addColumn($column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    public function actions(array $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    public function bulkActions(array $actions): static
    {
        $this->bulkActions = $actions;
        return $this;
    }

    public function defaultSort(string $column, string $direction = 'desc'): static
    {
        $this->defaultSort = [$column, $direction];
        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function searchable(bool $on = true): static
    {
        $this->searchable = $on;
        return $this;
    }

    public function searchPlaceholder(string $text): static
    {
        $this->searchPlaceholder = $text;
        return $this;
    }

    public function get(): array
    {
        return [
            'columns' => array_map(fn($c) => $c->toArray(), $this->columns),
            'filters' => array_map(fn($f) => $f->toArray(), $this->filters),
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
            'bulkActions' => array_map(fn($a) => $a->toArray(), $this->bulkActions),
            'defaultSort' => $this->defaultSort,
            'perPage' => $this->perPage,
            'searchable' => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder ?? 'Search...'
        ];
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function applyFilters(Builder $query, array $filterValues): Builder
    {
        foreach ($this->filters as $filter) {
            if (isset($filterValues[$filter->key()])) {
                $filter->apply($query, $filterValues[$filter->key()]);
            }
        }

        return $query;
    }

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

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    public function hasBulkActions(): bool
    {
        return !empty($this->bulkActions);
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getSearchPlaceholder(): string
    {
        return $this->searchPlaceholder ?? 'Search...';
    }
}
