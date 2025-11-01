<?php

namespace Monstrex\Ave\Core\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Table;

class TableQueryBuilder
{
    protected Builder $query;
    protected ?string $searchTerm = null;
    protected array $filters = [];
    protected ?array $sort = null;
    protected int $perPage = 25;
    protected int $page = 1;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public static function for(Builder $query): static
    {
        return new static($query);
    }

    /**
     * Static facade method for applying table query builder logic
     *
     * This is the primary way to use TableQueryBuilder from controllers.
     * It handles search, filters, and sorting based on request input.
     */
    public static function apply(Builder $query, Table $table, Request $request): Builder
    {
        $search = trim((string) $request->get('q', ''));
        if ($search !== '' && $table->isSearchable()) {
            $query = $table->applySearch($query, $search);
        }

        $filterValues = [];
        foreach ($table->getFilters() as $filter) {
            if (!method_exists($filter, 'key')) {
                continue;
            }

            $key = $filter->key();
            if ($request->exists($key)) {
                $filterValues[$key] = $request->input($key);
            }
        }

        if (!empty($filterValues)) {
            $query = $table->applyFilters($query, $filterValues);
        }

        $sortColumn = $request->get('sort');
        if ($sortColumn) {
            $sortDirection = strtolower((string) $request->get('dir', $request->get('direction', 'asc')));
            if (!in_array($sortDirection, ['asc', 'desc'], true)) {
                $sortDirection = 'asc';
            }

            $query->orderBy($sortColumn, $sortDirection);
        } elseif ($defaultSort = $table->getDefaultSort()) {
            [$column, $direction] = $defaultSort;
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Get per-page value from table configuration
     */
    public static function getPerPage(Table $table): int
    {
        return $table->getPerPage();
    }

    public function search(string $term): static
    {
        $this->searchTerm = $term;
        return $this;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    public function sort(?string $column, ?string $direction = null): static
    {
        if ($column) {
            $this->sort = [
                'column'    => $column,
                'direction' => $direction ?? 'asc',
            ];
        }
        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function page(int $page): static
    {
        $this->page = $page;
        return $this;
    }

    public function applySearch($searchable = []): static
    {
        if (!$this->searchTerm || empty($searchable)) {
            return $this;
        }

        $this->query->where(function ($q) use ($searchable) {
            foreach ($searchable as $column) {
                $q->orWhere($column, 'LIKE', "%{$this->searchTerm}%");
            }
        });

        return $this;
    }

    public function applyFilters($filterObjects = []): static
    {
        if (empty($this->filters)) {
            return $this;
        }

        foreach ($filterObjects as $filter) {
            if (!method_exists($filter, 'key')) {
                continue;
            }

            if (isset($this->filters[$filter->key()])) {
                $filter->apply($this->query, $this->filters[$filter->key()]);
            }
        }

        return $this;
    }

    public function applySort(): static
    {
        if ($this->sort) {
            $this->query->orderBy($this->sort['column'], $this->sort['direction']);
        }

        return $this;
    }

    public function paginate(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->query->paginate($this->perPage, ['*'], 'page', $this->page);
    }

    public function get(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query->get();
    }

    public function count(): int
    {
        return $this->query->count();
    }
}
