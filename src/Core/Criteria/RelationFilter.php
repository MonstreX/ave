<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RelationFilter extends AbstractCriterion
{
    public function __construct(
        protected string $relation,
        protected string $column,
        protected string $param,
        protected string $label = '',
    ) {
        parent::__construct($param, 35);
    }

    public function active(Request $request): bool
    {
        $value = $request->query($this->param);

        if (is_array($value)) {
            return array_filter($value, fn ($item) => $item !== null && $item !== '') !== [];
        }

        return $value !== null && $value !== '';
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $value = $request->query($this->param);

        if ($value === null || $value === '' || $value === []) {
            return $query;
        }

        $query->whereHas($this->relation, function (Builder $builder) use ($value) {
            if (is_array($value)) {
                $builder->whereIn($this->column, $value);
            } else {
                $builder->where($this->column, '=', $value);
            }
        });

        return $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (!$this->active($request)) {
            return null;
        }

        $value = $request->query($this->param);
        $display = is_array($value) ? implode(', ', $value) : $value;
        $label = $this->label ?: ucfirst(str_replace('_', ' ', $this->param));

        return ActionBadge::make("{$label}: {$display}")
            ->key($this->param)
            ->value($value)
            ->variant('secondary');
    }
}

