<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Monstrex\Ave\Support\CleanJsonResponse;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Exceptions\ResourceException;

class UpdateGroupAction extends AbstractResourceAction
{
    public function __construct(ResourceManager $resources)
    {
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

        if ($table->getDisplayMode() !== 'sortable-grouped') {
            throw new ResourceException("Resource '{$slug}' does not support grouped sortable mode", 422);
        }

        $groupColumn = $table->getGroupByColumn();

        if (!$groupColumn) {
            throw new ResourceException("No group column configured for resource '{$slug}'", 422);
        }

        $validated = $request->validate([
            'item_id' => 'required|integer',
            'group_column' => 'required|string',
            'group_id' => 'required|integer',
        ]);

        if ($validated['group_column'] !== $groupColumn) {
            throw new ResourceException('Invalid group column specified', 422);
        }

        $model = $modelClass::findOrFail($validated['item_id']);
        $user = $request->user();

        if (!$resource->can('update', $user, $model)) {
            throw ResourceException::unauthorized($slug, 'update');
        }

        $oldGroupId = $model->getAttribute($groupColumn);
        $model->setAttribute($groupColumn, $validated['group_id']);
        $model->save();

        return CleanJsonResponse::make([
            'success' => true,
            'message' => 'Item moved to new group successfully',
            'old_group' => $oldGroupId,
            'new_group' => $validated['group_id'],
        ]);
    }
}
