<?php

namespace Monstrex\Ave\Core\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Monstrex\Ave\Contracts\Persistable;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Events\ResourceCreating;
use Monstrex\Ave\Events\ResourceCreated;
use Monstrex\Ave\Events\ResourceUpdating;
use Monstrex\Ave\Events\ResourceUpdated;
use Monstrex\Ave\Events\ResourceDeleting;
use Monstrex\Ave\Events\ResourceDeleted;

/**
 * Handles persistence operations for resources
 *
 * Manages CRUD operations with transaction support, validation, and event dispatching
 */
class ResourcePersistence implements Persistable
{
    /**
     * Create a new model instance from form data
     *
     * @param string $resourceClass Resource class name
     * @param Form $form Form instance
     * @param array $data Validated form data
     * @param Request $request Current request
     * @return Model Created model
     */
    public function create(string $resourceClass, Form $form, array $data, Request $request): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $data, $request) {
            // Fire creating event
            event(new ResourceCreating($resourceClass, $data));

            // Merge form data (handle Fieldset serialization)
            $merged = $this->mergeFormData($form, null, $data, $request);

            // Create model
            $modelClass = $resourceClass::$model;
            $model = $modelClass::create($merged);

            // Sync relations
            $this->syncRelations($resourceClass, $model, $data, $request);

            // Fire created event
            event(new ResourceCreated($resourceClass, $model));

            return $model;
        });
    }

    /**
     * Update an existing model instance
     *
     * @param string $resourceClass Resource class name
     * @param Form $form Form instance
     * @param Model $model Model to update
     * @param array $data Validated form data
     * @param Request $request Current request
     * @return Model Updated model
     */
    public function update(string $resourceClass, Form $form, Model $model, array $data, Request $request): Model
    {
        return DB::transaction(function () use ($resourceClass, $form, $model, $data, $request) {
            // Fire updating event
            event(new ResourceUpdating($resourceClass, $model, $data));

            // Merge form data
            $merged = $this->mergeFormData($form, $model, $data, $request);

            // Update model
            $model->update($merged);

            // Sync relations
            $this->syncRelations($resourceClass, $model, $data, $request);

            // Fire updated event
            event(new ResourceUpdated($resourceClass, $model));

            return $model;
        });
    }

    /**
     * Delete a model instance
     *
     * @param string $resourceClass Resource class name
     * @param Model $model Model to delete
     * @return void
     */
    public function delete(string $resourceClass, Model $model): void
    {
        DB::transaction(function () use ($resourceClass, $model) {
            // Fire deleting event
            event(new ResourceDeleting($resourceClass, $model));

            // Delete model
            $model->delete();

            // Fire deleted event
            event(new ResourceDeleted($resourceClass, $model));
        });
    }

    /**
     * Merge form data with Fieldset handling
     *
     * Processes form data by iterating through form rows/columns/fields
     * and properly handling Fieldset serialization using the Fieldset's serialize() method
     *
     * @param Form $form Form instance
     * @param Model|null $model Model instance for extraction
     * @param array $data Form data from request
     * @param Request $request Current request
     * @return array Merged data ready for model persistence
     */
    protected function mergeFormData(Form $form, ?Model $model, array $data, Request $request): array
    {
        $payload = [];

        // Iterate through all form rows
        foreach ($form->rows() as $row) {
            // Each row has columns
            foreach ($row['columns'] as $col) {
                // Each column has fields
                foreach ($col['fields'] as $field) {
                    $key = $field->key();

                    // Handle Fieldset fields specially
                    if ($field instanceof Fieldset) {
                        // Get incoming data for this fieldset
                        $incoming = $request->input($key, []);

                        // Ensure it's an array and convert to indexed array
                        $incoming = is_array($incoming) ? array_values($incoming) : [];

                        // Use Fieldset's serialize() method for proper handling
                        if (method_exists($field, 'serialize')) {
                            $payload[$key] = $field->serialize($incoming);
                        } else {
                            // Fallback to JSON encoding if serialize() not available
                            $payload[$key] = json_encode($incoming);
                        }
                        continue;
                    }

                    // For normal fields, only include if in data array
                    if (array_key_exists($key, $data)) {
                        $payload[$key] = $data[$key];
                    }
                }
            }
        }

        return $payload;
    }

    /**
     * Sync relations after create/update
     *
     * @param string $resourceClass Resource class name
     * @param Model $model Model instance
     * @param array $data Form data
     * @param Request $request Current request
     * @return void
     */
    protected function syncRelations(string $resourceClass, Model $model, array $data, Request $request): void
    {
        // Check if resource has relations to sync
        if (!method_exists($resourceClass, 'syncRelations')) {
            return;
        }

        $resourceClass::syncRelations($model, $data, $request);
    }
}
