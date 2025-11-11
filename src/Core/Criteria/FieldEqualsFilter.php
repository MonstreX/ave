<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FieldEqualsFilter extends AbstractCriterion
{
    public function __construct(
        protected string $field,
        protected string $param,
        protected string $operator = '=',
        protected string $label = '',
    ) {
        parent::__construct($param, 30);
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

        if (is_array($value)) {
            $query->whereIn($this->field, $value);
            return $query;
        }

        $query->where($this->field, $this->operator, $value);

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
            ->variant('primary');
    }
}

