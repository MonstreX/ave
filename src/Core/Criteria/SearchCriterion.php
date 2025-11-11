<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchCriterion extends AbstractCriterion
{
    public function __construct(
        protected array $columns,
        protected string $param = 'q',
    ) {
        parent::__construct('search', 10);
    }

    public function active(Request $request): bool
    {
        return trim((string) $request->query($this->param, '')) !== '';
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $term = trim((string) $request->query($this->param, ''));
        if ($term === '' || empty($this->columns)) {
            return $query;
        }

        $query->where(function (Builder $builder) use ($term) {
            foreach ($this->columns as $column) {
                $builder->orWhere($column, 'LIKE', '%' . $term . '%');
            }
        });

        return $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (!$this->active($request)) {
            return null;
        }

        $term = $request->query($this->param);

        return ActionBadge::make('Search: ' . Str::limit($term, 30))
            ->key($this->param)
            ->value($term)
            ->variant('info');
    }
}
