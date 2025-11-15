<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction as BulkActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\HandlesResourceActions;

class RunBulkAction extends AbstractResourceAction
{
    use HandlesResourceActions;

    public function __construct(ResourceManager $resources)
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $action)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $actionInstance = $resourceClass::findAction($action, BulkActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $modelClass = $resourceClass::$model;
        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $requestedIds = collect($validated['ids'])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values();

        $keyName = $modelClass::make()->getKeyName();
        $models = $modelClass::whereIn($keyName, $requestedIds->all())->get();

        if ($models->isEmpty()) {
            throw ResourceException::modelNotFound($slug, implode(',', $requestedIds->all()));
        }

        $requestedStringIds = $requestedIds->map(fn ($id) => (string) $id);
        $foundStringIds = $models->pluck($keyName)->map(fn ($id) => (string) $id);
        $missing = $requestedStringIds->diff($foundStringIds);

        if ($missing->isNotEmpty()) {
            throw ResourceException::modelNotFound($slug, implode(',', $missing->all()));
        }

        $ability = $actionInstance->ability() ?? 'update';
        $unauthorizedModels = $models->filter(
            fn ($model) => !$resource->can($ability, $request->user(), $model)
        );

        if ($unauthorizedModels->isNotEmpty()) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::bulk($resourceClass, $request->user(), $models, $requestedIds->all());
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);
        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }
}
