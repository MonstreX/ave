<?php

namespace Monstrex\Ave\Core\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Contracts\Persistable;
use Monstrex\Ave\Core\DataSources\ModelDataSource;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Events\ResourceCreated;
use Monstrex\Ave\Events\ResourceCreating;
use Monstrex\Ave\Events\ResourceDeleted;
use Monstrex\Ave\Events\ResourceDeleting;
use Monstrex\Ave\Events\ResourceUpdated;
use Monstrex\Ave\Events\ResourceUpdating;

class ResourcePersistence implements Persistable
{
    public function create(string $resourceClass, Form $form, array $data, Request $request, FormContext $context): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $data, $request, $context) {
            event(new ResourceCreating($resourceClass, $data));

            $payload = $this->mergeFormData($form, null, $data, $request, $context);

            $modelClass = $resourceClass::$model;
            $model = $modelClass::create($payload);

            $this->syncRelations($resourceClass, $model, $data, $request);
            $context->setRecord($model);
            $context->runDeferredActions($model);

            event(new ResourceCreated($resourceClass, $model));

            return $model;
        });
    }

    public function update(string $resourceClass, Form $form, Model $model, array $data, Request $request, FormContext $context): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $model, $data, $request, $context) {
            event(new ResourceUpdating($resourceClass, $model, $data));

            $payload = $this->mergeFormData($form, $model, $data, $request, $context);

            $model->update($payload);

            $this->syncRelations($resourceClass, $model, $data, $request);
            $context->setRecord($model);
            $context->runDeferredActions($model);

            event(new ResourceUpdated($resourceClass, $model));

            return $model;
        });
    }

    public function delete(string $resourceClass, Model $model): void
    {
        DB::transaction(function () use ($resourceClass, $model) {
            event(new ResourceDeleting($resourceClass, $model));

            $model->delete();

            event(new ResourceDeleted($resourceClass, $model));
        });
    }

    protected function mergeFormData(Form $form, ?Model $model, array $data, Request $request, FormContext $context): array
    {
        $payload = [];

        foreach ($form->getAllFields() as $field) {
            $key = $field->key();
            $value = $data[$key] ?? $request->input($key);

            if ($field instanceof HandlesPersistence) {
                $result = $field->prepareForSave($value, $request, $context);
                $payload[$key] = $result->value();

                foreach ($result->deferredActions() as $action) {
                    $context->registerDeferredAction($action);
                }

                continue;
            }

            $payload[$key] = $field->extract($value);
        }

        return $payload;
    }

    protected function syncRelations(string $resourceClass, Model $model, array $data, Request $request): void
    {
        // Allow custom sync logic in resource
        if (method_exists($resourceClass, 'syncRelations')) {
            $resourceClass::syncRelations($model, $data, $request);
        }

        // Apply relation fields (BelongsToMany, etc.) via applyToDataSource
        $this->applyRelationFields($resourceClass, $model, $data);
    }

    /**
     * Apply relation fields to model (e.g., BelongsToMany sync)
     */
    protected function applyRelationFields(string $resourceClass, Model $model, array $data): void
    {
        $form = $resourceClass::form(null);
        $dataSource = new ModelDataSource($model);

        foreach ($form->getAllFields() as $field) {
            $key = $field->key();
            $value = $data[$key] ?? null;

            // Only apply if field has custom applyToDataSource logic (relations, etc.)
            // Check if method is overridden from AbstractField
            $reflection = new \ReflectionMethod($field, 'applyToDataSource');
            $declaringClass = $reflection->getDeclaringClass()->getName();

            // If declared in field class itself (not AbstractField), apply it
            if ($declaringClass !== \Monstrex\Ave\Core\Fields\AbstractField::class) {
                $field->applyToDataSource($dataSource, $value);
            }
        }
    }
}
