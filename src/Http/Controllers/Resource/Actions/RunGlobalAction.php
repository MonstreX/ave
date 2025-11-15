<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\GlobalAction as GlobalActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\HandlesResourceActions;

class RunGlobalAction extends AbstractResourceAction
{
    use HandlesResourceActions;

    public function __construct(ResourceManager $resources)
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $action)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $actionInstance = $resourceClass::findAction($action, GlobalActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $ability = $actionInstance->ability() ?? 'viewAny';
        if (!$resource->can($ability, $request->user())) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::global($resourceClass, $request->user());
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);
        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }
}
