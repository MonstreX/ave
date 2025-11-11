<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SortCriterion extends AbstractCriterion
{
    public function __construct(
        protected array $sortableColumns,
        protected ?array $defaultSort = null,
        protected string $param = 'sort',
        protected string $directionParam = 'dir',
    ) {
        parent::__construct('sort', 80);
    }

    public function active(Request $request): bool
    {
        return $request->query($this->param) !== null;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $column = $request->query($this->param);
        $direction = strtolower((string) $request->query($this->directionParam, 'asc'));

        if (!in_array($column, $this->sortableColumns, true)) {
            if ($this->defaultSort === null) {
                return $query;
            }
            [$column, $direction] = $this->defaultSort;
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        if ($column) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (!$this->active($request)) {
            return null;
        }

        $column = $request->query($this->param);
        $direction = strtolower((string) $request->query($this->directionParam, 'asc'));
        if (!in_array($column, $this->sortableColumns, true)) {
            return null;
        }

        $directionLabel = $direction === 'desc' ? 'DESC' : 'ASC';

        return ActionBadge::make('Sort: ' . $column . ' ' . $directionLabel)
            ->key($this->param)
            ->value($column)
            ->variant('secondary');
    }
}
