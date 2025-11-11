<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DateRangeCriterion extends AbstractCriterion
{
    public function __construct(
        protected string $column,
        protected string $param = 'date_range',
        protected string $label = '',
    ) {
        parent::__construct($param, 32);
    }

    public function active(Request $request): bool
    {
        $value = $this->range($request);

        return $value['from'] !== null || $value['to'] !== null;
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $range = $this->range($request);

        if ($range['from']) {
            $query->whereDate($this->column, '>=', $range['from']);
        }

        if ($range['to']) {
            $query->whereDate($this->column, '<=', $range['to']);
        }

        return $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (!$this->active($request)) {
            return null;
        }

        $range = $this->range($request);
        $label = $this->label ?: 'Date';
        $value = trim(sprintf('%s → %s', $range['from'] ?? '—', $range['to'] ?? '—'));

        return ActionBadge::make("{$label}: {$value}")
            ->key($this->param)
            ->value($range)
            ->variant('info');
    }

    protected function range(Request $request): array
    {
        $value = $request->query($this->param, []);

        return [
            'from' => Arr::get($value, 'from') ?? Arr::get($value, 'start'),
            'to' => Arr::get($value, 'to') ?? Arr::get($value, 'end'),
        ];
    }
}
