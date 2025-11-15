<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Core\Resource;

class StoreAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected FormValidator $validator,
        protected ResourcePersistence $persistence
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

        $blankModel = new $modelClass();
        $context = FormContext::forCreate([], $request, $blankModel);
        $rules = $this->validator->rulesFromForm(
            $form,
            $resourceClass,
            $request,
            mode: 'create',
            model: $blankModel,
            context: $context
        );

        try {
            $data = $request->validate($rules);
        } catch (ValidationException $exception) {
            $errorMessages = $this->formatValidationErrors($exception->errors());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessages,
                    'errors' => $exception->errors(),
                ], 422);
            }

            $request->session()->flash('toast', [
                'type' => 'danger',
                'message' => $errorMessages,
            ]);

            throw $exception;
        }

        $data = $resourceClass::beforeCreate($data, $request);

        $model = $this->persistence->create($resourceClass, $form, $data, $request, $context);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Created successfully'),
                'reload' => true,
                'data' => $model->fresh()->toArray(),
            ]);
        }

        return $this->redirectAfterSave($request, $slug, $model, 'create');
    }
}
