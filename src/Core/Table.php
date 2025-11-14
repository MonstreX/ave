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
    protected bool $searchable = true;
    protected ?string $searchPlaceholder = null;

    // Display modes
    protected string $displayMode = 'table';
    protected ?string $orderColumn = null;
    protected ?string $parentColumn = null;
    protected int $treeMaxDepth = 5;
    /**
     * Upper bound for instant-loading datasets (tree / sortable modes).
     * Keeps payloads cacheable per .doc/CORE-CHEKLIST.md (Cacheable & lightweight schemas).
     */
    protected int $maxInstantLoad = 500;
    /**
     * When true, controller must stop instant loading if limit is exceeded instead of falling back silently.
     */
    protected bool $forcePaginationOnOverflow = true;

    // Grouping
    protected ?string $groupByColumn = null;
    protected ?string $groupByRelation = null;
    protected ?string $groupByOrderColumn = 'order';

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

    /**
     * Set display mode for the table.
     */
    public function displayMode(string $mode): static
    {
        $this->displayMode = $mode;
        return $this;
    }

    /**
     * Configure sortable list mode.
     */
    public function sortable(string $orderColumn = 'order'): static
    {
        $this->displayMode = 'sortable';
        $this->orderColumn = $orderColumn;
        return $this;
    }

    /**
     * Configure tree view mode.
     * All displayed data must be defined in columns() - no separate labelColumn.
     */
    public function tree(
        string $parentColumn = 'parent_id',
        string $orderColumn = 'order',
        int $maxDepth = 5
    ): static {
        $this->displayMode = 'tree';
        $this->parentColumn = $parentColumn;
        $this->orderColumn = $orderColumn;
        $this->treeMaxDepth = $maxDepth;
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

    /**
     * Get display mode.
     */
    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    /**
     * Get order column name.
     */
    public function getOrderColumn(): ?string
    {
        return $this->orderColumn;
    }

    /**
     * Get parent column name.
     */
    public function getParentColumn(): ?string
    {
        return $this->parentColumn;
    }

    /**
     * Get tree max depth.
     */
    public function getTreeMaxDepth(): int
    {
        return $this->treeMaxDepth;
    }

    /**
     * Configure grouped sortable mode.
     * Records will be grouped by the specified column and sorted within each group.
     *
     * @param string $groupColumn Column name for grouping (e.g., "group_id")
     * @param string|null $relation Relation name for loading group data (e.g., "group")
     * @param string $groupOrderColumn Column for ordering groups (default: "order")
     * @param string $itemOrderColumn Column for ordering items within groups (default: "order")
     */
    public function groupedSortable(
        string $groupColumn,
        ?string $relation = null,
        string $groupOrderColumn = 'order',
        string $itemOrderColumn = 'order'
    ): static {
        $this->displayMode = 'sortable-grouped';
        $this->groupByColumn = $groupColumn;
        $this->groupByRelation = $relation;
        $this->groupByOrderColumn = $groupOrderColumn;
        $this->orderColumn = $itemOrderColumn;
        return $this;
    }

    /**
     * Configure the maximum amount of records that can be loaded instantly for tree/sortable modes.
     */
    public function maxInstantLoad(int $limit): static
    {
        $this->maxInstantLoad = max(1, $limit);
        return $this;
    }

    /**
     * Allow forcing an error when the instant load limit is exceeded.
     */
    public function forcePaginationOnOverflow(bool $force = true): static
    {
        $this->forcePaginationOnOverflow = $force;
        return $this;
    }

    /**
     * Get group by column name.
     */
    public function getGroupByColumn(): ?string
    {
        return $this->groupByColumn;
    }

    /**
     * Get group by relation name.
     */
    public function getGroupByRelation(): ?string
    {
        return $this->groupByRelation;
    }

    /**
     * Get group order column name.
     */
    public function getGroupByOrderColumn(): string
    {
        return $this->groupByOrderColumn ?? 'order';
    }

    /**
     * Get configured instant-load ceiling.
     */
    public function getMaxInstantLoad(): int
    {
        return $this->maxInstantLoad;
    }

    /**
     * Whether controller must enforce the limit strictly.
     */
    public function shouldForceInstantLoadLimit(): bool
    {
        return $this->forcePaginationOnOverflow;
    }
}
