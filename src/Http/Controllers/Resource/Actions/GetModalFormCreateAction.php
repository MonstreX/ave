<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Monstrex\Ave\Support\CleanJsonResponse;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Exceptions\ResourceException;

class GetModalFormCreateAction extends AbstractResourceAction
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

        $modelClass = $resourceClass::$model;
        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $model = new $modelClass();

        foreach ($request->query() as $key => $value) {
            $model->$key = $value;
        }

        $form = $resourceClass::form($request);
        $context = FormContext::forCreate([], $request, $model);

        foreach ($form->getAllFields() as $field) {
            $field->fillFromModel($model);
        }

        $formLayout = $form->layout();

        $html = view('ave::partials.modals.form-modal', [
            'form' => $form,
            'formLayout' => $formLayout,
            'model' => $model,
            'context' => $context,
            'slug' => $slug,
            'mode' => 'create',
        ])->render();

        return CleanJsonResponse::make([
            'success' => true,
            'formHtml' => $html,
            'currentData' => [],
        ]);
    }
}
