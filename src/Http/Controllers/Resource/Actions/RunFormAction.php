<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\FormAction as FormActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\HandlesResourceActions;

class RunFormAction extends AbstractResourceAction
{
    use HandlesResourceActions;

    public function __construct(ResourceManager $resources)
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $action, ?string $id = null)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize(
            $slug,
            $id ? 'update' : 'create',
            $request
        );

        $actionInstance = $resourceClass::findAction($action, FormActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $model = $id
            ? $this->findModelOrFail($resourceClass, $slug, $id)
            : (new ($resourceClass::$model)());

        $ability = $actionInstance->ability() ?? ($id ? 'update' : 'create');

        if (!$resource->can($ability, $request->user(), $id ? $model : null)) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::form($resourceClass, $request->user(), $model);
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);

        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }
}
