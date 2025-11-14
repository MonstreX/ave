<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Support\Http\RequestDebugSanitizer;

class UpdateAction extends AbstractResourceAction
{
    public function __construct(
        ResourceManager $resources,
        protected FormValidator $validator,
        protected ResourcePersistence $persistence,
        protected RequestDebugSanitizer $requestSanitizer
    ) {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $id)
    {
        [$resourceClass] = $this->resolveAndAuthorize($slug, 'update', $request);

        $form = $resourceClass::form($request);
        $model = $this->findModelOrFail($resourceClass, $slug, $id);

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
            $traceId = $this->logValidationFailure(
                'update',
                $resourceClass,
                $slug,
                $request,
                $rules,
                $form,
                $model,
                $exception->errors()
            );

            $errorMessages = $this->formatValidationErrors($exception->errors());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessages,
                    'errors' => $exception->errors(),
                    'trace_id' => $traceId,
                ], 422);
            }

            $request->session()->flash('toast', [
                'type' => 'danger',
                'message' => $errorMessages,
                'trace_id' => $traceId,
            ]);

            throw $exception;
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

    protected function logValidationFailure(
        string $stage,
        string $resourceClass,
        string $slug,
        Request $request,
        array $rules,
        Form $form,
        mixed $model,
        array $errors = []
    ): string {
        $traceId = (string) \Illuminate\Support\Str::uuid();
        $sensitiveKeys = array_map(
            static fn ($field) => method_exists($field, 'key') ? $field->key() : '',
            $form->getAllFields()
        );

        $sanitizedInput = $this->requestSanitizer->sanitize($request, $sensitiveKeys);

        \Log::error("Resource validation failed on {$stage}", [
            'trace_id' => $traceId,
            'resource' => $resourceClass,
            'slug' => $slug,
            'model_id' => $model?->getKey(),
            'errors' => $errors,
            'input' => $sanitizedInput,
            'rules' => array_keys($rules),
        ]);

        return $traceId;
    }
}
