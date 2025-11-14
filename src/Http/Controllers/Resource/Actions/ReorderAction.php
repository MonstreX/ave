<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Exceptions\ResourceException;

class ReorderAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected SortableOrderService $sortingService
    ) {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request);

        $modelClass = $resourceClass::$model;
        $table = $resourceClass::table($request);

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $this->validateSortableMode($table, $slug);

        $orderColumn = $table->getOrderColumn() ?? 'order';
        $order = $this->normalizeOrderInput($request, $orderColumn);

        $this->sortingService->reorder(
            $order,
            $orderColumn,
            $resource,
            $request->user(),
            $modelClass,
            $slug
        );

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
        ]);
    }
}
