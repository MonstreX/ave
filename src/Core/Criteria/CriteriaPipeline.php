<?php

namespace Monstrex\Ave\Core\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Criteria\Contracts\Criterion;
use Monstrex\Ave\Core\Filters\Filter;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Table;

class CriteriaPipeline
{
    /**
     * @param array<int,Criterion> $criteria
     */
    public function __construct(
        protected array $criteria,
        protected Request $request,
    ) {
        usort($this->criteria, fn (Criterion $a, Criterion $b) => $a->priority() <=> $b->priority());
    }

    public static function make(string $resourceClass, Table $table, Request $request): self
    {
        /** @var class-string<Resource> $resourceClass */
        $criteria = [];

        $searchableColumns = $resourceClass::searchableColumns($table);
        if (!empty($searchableColumns)) {
            $criteria[] = new SearchCriterion($searchableColumns);
        }

        $sortableColumns = $resourceClass::sortableColumns($table);
        if (!empty($sortableColumns) || $table->getDefaultSort()) {
            $criteria[] = new SortCriterion($sortableColumns, $table->getDefaultSort());
        }

        if ($resourceClass::usesSoftDeletes()) {
            $criteria[] = new SoftDeleteCriterion();
        }

        foreach ($resourceClass::getCriteria() as $criterion) {
            $instance = self::resolveCriterion($criterion);
            if ($instance) {
                $criteria[] = $instance;
            }
        }

        foreach ($table->getFilters() as $filter) {
            if ($filter instanceof Filter) {
                $criteria[] = new TableFilterCriterion($filter);
            }
        }

        return new self($criteria, $request);
    }

    /**
     * @param Builder $query
     */
    public function apply(Builder $query): Builder
    {
        foreach ($this->criteria as $criterion) {
            $query = $criterion->apply($query, $this->request);
        }

        return $query;
    }

    public function badges(): array
    {
        $badges = [];
        foreach ($this->criteria as $criterion) {
            $badge = $criterion->badge($this->request);
            if ($badge) {
                $badges[] = $badge->toArray();
            }
        }

        return $badges;
    }

    protected static function resolveCriterion(mixed $criterion): ?Criterion
    {
        if ($criterion instanceof Criterion) {
            return $criterion;
        }

        if (is_string($criterion) && class_exists($criterion)) {
            $resolved = new $criterion();
            return $resolved instanceof Criterion ? $resolved : null;
        }

        return null;
    }
}
