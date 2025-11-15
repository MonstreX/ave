<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\HandlesValidationErrors;

class UpdateAction extends AbstractResourceAction
{
    use HandlesValidationErrors;
    public function __construct(
        ResourceManager $resources,
        protected FormValidator $validator,
        protected ResourcePersistence $persistence
    ) {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resolveResourceClass($slug);

        $form = $resourceClass::form($request);
        $model = $this->findModelOrFail($resourceClass, $slug, $id);
        $this->resolveAndAuthorize($slug, 'update', $request, $model);

        $context = FormContext::forEdit($model, [], $request);
        $rules = $this->validator->rulesFromForm(
            $form,
            $resourceClass,
            $request,
            mode: 'edit',
            model: $model,
            context: $context
        );

        try {
            $data = $request->validate($rules);
        } catch (ValidationException $exception) {
            return $this->handleValidationException($exception, $request);
        }

        $model = $this->persistence->update($resourceClass, $form, $model, $data, $request, $context);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Updated successfully'),
                'reload' => true,
                'data' => $model->fresh()->toArray(),
            ]);
        }

        return $this->redirectAfterSave($request, $slug, $model, 'edit');
    }
}
