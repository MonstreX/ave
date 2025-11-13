<?php

namespace Monstrex\Ave\Core;

use Monstrex\Ave\Core\Columns\Column;

class Table
{
    /** @var array */
    protected array $columns = [];

    /** @var array */
    protected array $filters = [];

    protected ?array $defaultSort = null;
    protected int $perPage = 25;
    protected array $perPageOptions = [10, 25, 50, 100];
    protected bool $showPerPageSelector = true;
    protected bool $loadAll = false;
    protected ?int $maxLoadAll = null;
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

    public function perPageOptions(array $options): static
    {
        $this->perPageOptions = $options;
        return $this;
    }

    public function showPerPageSelector(bool $show = true): static
    {
        $this->showPerPageSelector = $show;
        return $this;
    }

    public function loadAll(bool $loadAll = true, ?int $maxRecords = null): static
    {
        $this->loadAll = $loadAll;
        if ($maxRecords !== null) {
            $this->maxLoadAll = $maxRecords;
        }
        return $this;
    }

    public function maxLoadAll(?int $max): static
    {
        $this->maxLoadAll = $max;
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
            'columns' => array_map(function ($column) {
                if (method_exists($column, 'toDefinition')) {
                    return $column->toDefinition()->toArray();
                }

                if (method_exists($column, 'toArray')) {
                    return $column->toArray();
                }

                return (array) $column;
            }, $this->columns),
            'filters' => array_map(fn($f) => $f->toArray(), $this->filters),
            'defaultSort' => $this->defaultSort,
            'perPage' => $this->perPage,
            'perPageOptions' => $this->perPageOptions,
            'showPerPageSelector' => $this->showPerPageSelector,
            'searchable' => $this->searchable,
            'searchPlaceholder' => $this->searchPlaceholder ?? 'Search...'
        ];
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getDefaultSort(): ?array
    {
        return $this->defaultSort;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getSearchPlaceholder(): string
    {
        return $this->searchPlaceholder ?? 'Search...';
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getPerPageOptions(): array
    {
        return $this->perPageOptions;
    }

    public function shouldShowPerPageSelector(): bool
    {
        return $this->showPerPageSelector;
    }

    public function shouldLoadAll(): bool
    {
        return $this->loadAll;
    }

    public function getMaxLoadAll(): ?int
    {
        return $this->maxLoadAll;
    }

    public function findInlineColumn(string $field): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column instanceof Column
                && $column->supportsInline()
                && $column->inlineField() === $field) {
                return $column;
            }
        }

        return null;
    }
}
