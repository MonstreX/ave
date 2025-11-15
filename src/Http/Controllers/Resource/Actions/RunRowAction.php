<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\RowAction as RowActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\HandlesResourceActions;

class RunRowAction extends AbstractResourceAction
{
    use HandlesResourceActions;

    public function __construct(ResourceManager $resources)
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $id, string $action)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'view', $request);

        $actionInstance = $resourceClass::findAction($action, RowActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $model = $this->findModelOrFail($resourceClass, $slug, $id);

        $ability = $actionInstance->ability() ?? 'update';
        if (!$resource->can($ability, $request->user(), $model)) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::row($resourceClass, $request->user(), $model);
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);

        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }
}
