<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Exceptions\ResourceException;

class EditAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected ResourceRenderer $renderer
    ) {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resolveResourceClass($slug);

        $model = $this->findModelOrFail($resourceClass, $slug, $id);
        $this->resolveAndAuthorize($slug, 'update', $request, $model);
        $form = $resourceClass::form($request);

        return $this->renderer->form($resourceClass, $form, $model, $request);
    }
}
