<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Criteria\Contracts\Criterion;

abstract class AbstractCriterion implements Criterion
{
    public function __construct(
        protected string $key,
        protected int $priority = 50,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function active(Request $request): bool
    {
        $value = $request->query($this->key);
        if (is_array($value)) {
            return array_filter($value, fn ($item) => $item !== null && $item !== '') !== [];
        }

        return $value !== null && $value !== '';
    }

    public function apply(Builder $query, Request $request): Builder
    {
        return $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        return null;
    }
}

