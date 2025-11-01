<?php

namespace Monstrex\Ave\Core\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
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
     *
     * @param Builder $query Eloquent query builder
     * @param Table $table Table configuration
     * @param Request $request Current request
     * @return Builder Modified query builder
     */
    public static function apply(Builder $query, Table $table, Request $request): Builder
    {
        // Get table configuration
        $tableConfig = $table->get();

        // Apply search
        $search = trim((string) $request->get('q', ''));
        if ($search !== '' && !empty($tableConfig['searchable'] ?? [])) {
            $query = $table->applySearch($query, $search);
        }

        // Apply filters
        $filters = $request->only(
            array_map(fn($f) => $f->key(), $tableConfig['filters'] ?? [])
        );
        if (!empty($filters)) {
            $query = $table->applyFilters($query, $filters);
        }

        // Apply sorting
        if ($request->has('sort')) {
            $sortColumn = $request->get('sort');
            $sortDirection = $request->get('direction', 'asc');
            if (in_array($sortDirection, ['asc', 'desc'])) {
                $query->orderBy($sortColumn, $sortDirection);
            }
        }

        return $query;
    }

    /**
     * Get per-page value from table configuration
     *
     * @param Table $table Table configuration
     * @return int Records per page
     */
    public static function getPerPage(Table $table): int
    {
        return $table->get()['perPage'] ?? 25;
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
