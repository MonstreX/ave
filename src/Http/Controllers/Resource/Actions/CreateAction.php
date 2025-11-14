<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Exceptions\ResourceException;

class CreateAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected ResourceRenderer $renderer
    ) {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug)
    {
        [$resourceClass] = $this->resolveAndAuthorize($slug, 'create', $request);

        $form = $resourceClass::form($request);
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $model = new $modelClass();

        return $this->renderer->form($resourceClass, $form, $model, $request);
    }
}
