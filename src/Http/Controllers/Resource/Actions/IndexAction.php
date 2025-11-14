<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Monstrex\Ave\Core\Criteria\CriteriaPipeline;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;

class IndexAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected ResourceRenderer $renderer
    ) {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $table = $resourceClass::table($request);
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $query = $modelClass::query();

        $request->attributes->set('ave.resource.class', $resourceClass);
        $request->attributes->set('ave.resource.instance', $resource);
        $request->attributes->set('ave.resource.model', $modelClass);

        $resourceClass::applyEagerLoading($query);

        $criteriaPipeline = CriteriaPipeline::make($resourceClass, $table, $request);
        $query = $criteriaPipeline->apply($query);
        $criteriaBadges = $criteriaPipeline->badges();

        $displayMode = $table->getDisplayMode();

        if (in_array($displayMode, ['tree', 'sortable', 'sortable-grouped'], true)) {
            $structureResult = $this->resolveRecordsForStructureMode(
                $request,
                $table,
                $query,
                $displayMode,
                $slug,
                $modelClass
            );

            $criteriaBadges = array_merge($criteriaBadges, $structureResult['badges']);

            return $this->renderer->index(
                $resourceClass,
                $table,
                $structureResult['records'],
                $request,
                $criteriaBadges
            );
        }

        $perPage = $this->resolvePerPage($request, $table, $slug);
        $records = $query->paginate($perPage)->appends($request->query());

        return $this->renderer->index($resourceClass, $table, $records, $request, $criteriaBadges);
    }

    protected function resolveRecordsForStructureMode(
        Request $request,
        Table $table,
        Builder $baseQuery,
        string $displayMode,
        string $slug,
        string $modelClass
    ): array {
        $limit = max(1, $table->getMaxInstantLoad());
        $forceLimit = $table->shouldForceInstantLoadLimit();
        $orderedQuery = clone $baseQuery;

        if ($displayMode === 'sortable-grouped') {
            $groupByColumn = $table->getGroupByColumn();
            $groupByRelation = $table->getGroupByRelation();
            $groupOrderColumn = $table->getGroupByOrderColumn();
            $itemOrderColumn = $table->getOrderColumn() ?? 'order';

            if ($groupByRelation) {
                $orderedQuery->with($groupByRelation);
                $model = new $modelClass();
                $relation = $model->{$groupByRelation}();
                $relatedTable = $relation->getRelated()->getTable();
                $foreignKey = $groupByColumn;
                $ownerKey = $relation->getOwnerKeyName();

                $orderedQuery->join(
                    $relatedTable,
                    "{$model->getTable()}.{$foreignKey}",
                    '=',
                    "{$relatedTable}.{$ownerKey}"
                )
                    ->orderBy("{$relatedTable}.{$groupOrderColumn}")
                    ->orderBy("{$model->getTable()}.{$itemOrderColumn}")
                    ->select("{$model->getTable()}.*");
            } else {
                $orderedQuery->orderBy($groupByColumn)
                    ->orderBy($itemOrderColumn);
            }
        } else {
            $orderColumn = $table->getOrderColumn() ?? 'order';
            $orderedQuery->orderBy($orderColumn);
        }

        $records = $orderedQuery
            ->limit($limit + 1)
            ->get();

        $limitExceeded = $records->count() > $limit;

        if ($limitExceeded && $forceLimit) {
            throw new ResourceException(
                "Resource '{$slug}' exceeds instant load limit of {$limit}. Apply filters or switch to paginated mode.",
                422
            );
        }

        if ($limitExceeded) {
            $records = $records->take($limit)->values();
        }

        $paginator = new LengthAwarePaginator(
            $records,
            $limitExceeded ? $limit + 1 : $records->count(),
            max($records->count(), 1),
            1,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $badges = [];
        if ($limitExceeded) {
            $badges[] = [
                'label' => "Limited to first {$limit} records (instant load cap)",
                'key' => 'instant-load-limit',
                'value' => $limit,
                'variant' => 'limit-warning',
            ];
        }

        return [
            'records' => $paginator,
            'badges' => $badges,
        ];
    }

    protected function resolvePerPage(Request $request, Table $table, string $slug): int
    {
        $perPage = $request->input('per_page');
        $session = $request->hasSession() ? $request->session() : null;

        if ($perPage !== null) {
            $perPage = (int) $perPage;
            if (!in_array($perPage, $table->getPerPageOptions(), true)) {
                throw new ResourceException("Invalid per_page value for resource '{$slug}'", 422);
            }

            if ($session) {
                $session->put("ave.resources.{$slug}.per_page", $perPage);
            }

            return $perPage;
        }

        $sessionPerPage = $session?->get("ave.resources.{$slug}.per_page");
        if ($sessionPerPage && in_array((int) $sessionPerPage, $table->getPerPageOptions(), true)) {
            return (int) $sessionPerPage;
        }

        return $table->getPerPage();
    }
}
