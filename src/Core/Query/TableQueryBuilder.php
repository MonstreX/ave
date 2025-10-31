<?php

namespace Monstrex\Ave\Core\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

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
