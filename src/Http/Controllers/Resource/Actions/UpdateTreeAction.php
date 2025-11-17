<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Exceptions\ResourceException;

class UpdateTreeAction extends AbstractResourceAction
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

        $table = $resourceClass::table($request);

        if ($table->getDisplayMode() !== 'tree') {
            throw new ResourceException("Resource '{$slug}' does not support tree mode", 422);
        }

        $validated = $request->validate([
            'tree' => 'required|array',
        ]);

        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $treePayload = $this->normalizeTreePayload($validated['tree']);

        $this->sortingService->rebuildTree(
            $treePayload,
            $table,
            $resource,
            $request->user(),
            $modelClass,
            $slug
        );

        return response()->json([
            'success' => true,
            'message' => 'Tree structure updated successfully',
        ]);
    }
}
