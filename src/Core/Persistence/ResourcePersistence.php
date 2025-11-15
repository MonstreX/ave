<?php

namespace Monstrex\Ave\Core\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Contracts\Persistable;
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

            $resourceClass::afterCreate($model, $request);

            return $model;
        });
    }

    public function update(string $resourceClass, Form $form, Model $model, array $data, Request $request, FormContext $context): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $model, $data, $request, $context) {
            event(new ResourceUpdating($resourceClass, $model, $data));

            $data = $resourceClass::beforeUpdate($model, $data, $request);

            $payload = $this->mergeFormData($form, $model, $data, $request, $context);

            $model->update($payload);

            $this->syncRelations($resourceClass, $model, $data, $request);
            $context->setRecord($model);
            $context->runDeferredActions($model);

            event(new ResourceUpdated($resourceClass, $model));

            $resourceClass::afterUpdate($model, $request);

            return $model;
        });
    }

    public function delete(string $resourceClass, Model $model, Request $request): void
    {
        DB::transaction(function () use ($resourceClass, $model, $request) {
            event(new ResourceDeleting($resourceClass, $model));

            $model->delete();

            event(new ResourceDeleted($resourceClass, $model));

            $resourceClass::afterDelete($model, $request);
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

                foreach ($result->deferredActions() as $action) {
                    $context->registerDeferredAction($action);
                }

                if (!$result->shouldPersist()) {
                    continue;
                }

                $payload[$key] = $result->value();
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

        // Note: Relation fields (BelongsToMany, etc.) are handled via HandlesPersistence
        // in mergeFormData(), which registers deferred actions executed after the model is saved.
    }
}
