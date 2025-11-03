<?php

namespace Monstrex\Ave\Core\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Monstrex\Ave\Contracts\Persistable;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Events\ResourceCreated;
use Monstrex\Ave\Events\ResourceCreating;
use Monstrex\Ave\Events\ResourceDeleted;
use Monstrex\Ave\Events\ResourceDeleting;
use Monstrex\Ave\Events\ResourceUpdated;
use Monstrex\Ave\Events\ResourceUpdating;

class ResourcePersistence implements Persistable
{
    public function create(string $resourceClass, Form $form, array $data, Request $request): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $data, $request) {
            event(new ResourceCreating($resourceClass, $data));

            $payload = $this->mergeFormData($form, null, $data, $request);

            $modelClass = $resourceClass::$model;
            $model = $modelClass::create($payload);

            $this->syncRelations($resourceClass, $model, $data, $request);

            event(new ResourceCreated($resourceClass, $model));

            return $model;
        });
    }

    public function update(string $resourceClass, Form $form, Model $model, array $data, Request $request): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $model, $data, $request) {
            event(new ResourceUpdating($resourceClass, $model, $data));

            $payload = $this->mergeFormData($form, $model, $data, $request);

            $model->update($payload);

            $this->syncRelations($resourceClass, $model, $data, $request);

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

    protected function mergeFormData(Form $form, ?Model $model, array $data, Request $request): array
    {
        $payload = [];

        foreach ($form->getAllFields() as $field) {
            $key = $field->key();

            if ($field instanceof Fieldset) {
                // CRITICAL: Call beforeApply for FieldSet to handle nested Media fields
                $context = $model ? \Monstrex\Ave\Core\FormContext::forEdit($model, [], $request)
                                  : \Monstrex\Ave\Core\FormContext::forCreate([], $request);
                $field->beforeApply($request, $context);

                // Get the prepared items from FieldSet after beforeApply processing
                // This includes Media collection names stored in JSON
                $incoming = $request->input($key, []);
                $normalized = $this->normalizeFieldsetValue($incoming);

                // Extract prepared value from field (which was set by beforeApply)
                $payload[$key] = $field->extract($normalized);
                continue;
            }

            $value = $data[$key] ?? $request->input($key);
            $payload[$key] = $field->extract($value);
        }

        return $payload;
    }

    protected function normalizeFieldsetValue(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }

    protected function syncRelations(string $resourceClass, Model $model, array $data, Request $request): void
    {
        if (!method_exists($resourceClass, 'syncRelations')) {
            return;
        }

        $resourceClass::syncRelations($model, $data, $request);
    }
}
