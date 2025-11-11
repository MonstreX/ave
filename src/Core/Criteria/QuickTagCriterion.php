<?php

namespace Monstrex\Ave\Core\Criteria;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QuickTagCriterion extends AbstractCriterion
{
    public function __construct(
        protected string $tag,
        protected Closure $callback,
        protected string $label,
        protected string $param = 'quick',
        protected ?string $variant = 'default',
    ) {
        parent::__construct($tag, 45);
    }

    public function active(Request $request): bool
    {
        return $request->query($this->param) === $this->tag;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        if (!$this->active($request)) {
            return $query;
        }

        ($this->callback)($query, $request);

        return $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (!$this->active($request)) {
            return null;
        }

        return ActionBadge::make($this->label)
            ->key($this->param)
            ->value($this->tag)
            ->variant($this->variant);
    }
}

