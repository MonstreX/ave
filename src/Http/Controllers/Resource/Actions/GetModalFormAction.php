<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Exceptions\ResourceException;

class GetModalFormAction extends AbstractResourceAction
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

        $modelClass = $resourceClass::$model;
        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $model = $modelClass::findOrFail($id);
        $this->resolveAndAuthorize($slug, 'update', $request, $model);

        $form = $resourceClass::form($request);
        $context = FormContext::forEdit($model, [], $request);

        foreach ($form->getAllFields() as $field) {
            $field->fillFromDataSource($context->dataSource());
        }

        $formLayout = $form->layout();

        $html = view('ave::partials.modals.form-modal', [
            'form' => $form,
            'formLayout' => $formLayout,
            'model' => $model,
            'context' => $context,
            'slug' => $slug,
            'mode' => 'edit',
        ])->render();

        return response()->json([
            'success' => true,
            'formHtml' => $html,
            'currentData' => $model->toArray(),
        ]);
    }
}
