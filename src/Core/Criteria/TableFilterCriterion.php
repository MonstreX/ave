<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Filters\Filter;

class TableFilterCriterion extends AbstractCriterion
{
    public function __construct(
        protected Filter $filter,
        protected int $priority = 60,
    ) {
        parent::__construct($filter->key(), $priority);
    }

    public function apply(Builder $query, Request $request): Builder
    {
        [$value, $hasValue] = $this->resolveValue($request);

        if (!$hasValue) {
            return $query;
        }

        return $this->filter->apply($query, $value);
    }

    public function badge(Request $request): ?ActionBadge
    {
        [$value, $hasValue] = $this->resolveValue($request);

        if (!$hasValue) {
            return null;
        }

        return ActionBadge::make($this->filter->getLabel() . ': ' . $this->filter->formatBadgeValue($value))
            ->key($this->filter->key())
            ->value($value)
            ->variant('primary');
    }

    /**
     * @return array{mixed,bool} [value, hasValue]
     */
    protected function resolveValue(Request $request): array
    {
        $key = $this->filter->key();

        if ($request->exists($key)) {
            $value = $request->query($key);
            return [$value, !$this->isEmpty($value)];
        }

        $default = $this->filter->getDefault();
        return [$default, !$this->isEmpty($default)];
    }

    protected function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return empty(array_filter($value, fn ($item) => $item !== null && $item !== ''));
        }

        return $value === null || $value === '';
    }
}

