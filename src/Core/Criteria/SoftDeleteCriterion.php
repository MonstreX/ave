<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SoftDeleteCriterion extends AbstractCriterion
{
    public function __construct(
        protected string $param = 'trashed',
    ) {
        parent::__construct('soft_delete', 60);
    }

    public function active(Request $request): bool
    {
        $value = $request->query($this->param);

        return in_array($value, ['with', 'only'], true);
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $value = $request->query($this->param);

        return match ($value) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => $query,
        };
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (!$this->active($request)) {
            return null;
        }

        $value = $request->query($this->param);
        $label = $value === 'with'
            ? 'With trashed'
            : 'Only trashed';

        return ActionBadge::make($label)
            ->key($this->param)
            ->value($value)
            ->variant('warning');
    }
}
