<?php

namespace Monstrex\Ave\Core\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        try {
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
        } catch (QueryException $e) {
            throw $this->convertQueryExceptionToValidation($e);
        }
    }

    public function update(string $resourceClass, Form $form, Model $model, array $data, Request $request, FormContext $context): Model
    {
        try {
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
        } catch (QueryException $e) {
            throw $this->convertQueryExceptionToValidation($e);
        }
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

    /**
     * Convert database QueryException to ValidationException with user-friendly message
     *
     * Handles common database errors like:
     * - NULL constraint violations (SQLSTATE 23000, error 1048)
     * - Unique constraint violations (SQLSTATE 23000, error 1062)
     * - Foreign key violations (SQLSTATE 23000, error 1452)
     */
    protected function convertQueryExceptionToValidation(QueryException $e): ValidationException
    {
        $message = $e->getMessage();
        $errorCode = $e->errorInfo[1] ?? null;

        // Extract field name from error message if possible
        $field = $this->extractFieldFromError($message);

        // Determine error type and create appropriate validation message
        $errors = match ($errorCode) {
            1048 => [$field => __('This field is required and cannot be empty.')],
            1062 => [$field => __('This value already exists. Please use a unique value.')],
            1452 => [$field => __('Invalid relationship value.')],
            default => ['database' => __('Database error: ') . $this->sanitizeErrorMessage($message)],
        };

        return ValidationException::withMessages($errors);
    }

    /**
     * Extract field name from SQL error message
     */
    protected function extractFieldFromError(string $message): string
    {
        // Try to extract column name from "Column 'field_name' cannot be null"
        if (preg_match("/Column '([^']+)' cannot be null/i", $message, $matches)) {
            return $matches[1];
        }

        // Try to extract from "Duplicate entry '...' for key 'table.field'"
        if (preg_match("/for key '(?:[^.]+\.)?([^']+)'/i", $message, $matches)) {
            // Remove common suffixes like _UNIQUE, _unique
            return preg_replace('/_(unique|UNIQUE|idx|INDEX)$/', '', $matches[1]);
        }

        // Try to extract from "Cannot add or update a child row: ... FOREIGN KEY (`field_name`)"
        if (preg_match("/FOREIGN KEY \(`([^`]+)`\)/i", $message, $matches)) {
            return $matches[1];
        }

        return 'general';
    }

    /**
     * Remove sensitive information from error message
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        // Remove SQL query part
        $message = preg_replace('/\(SQL:.*\)$/', '', $message);

        // Remove connection info
        $message = preg_replace('/\(Connection:.*?,/', '', $message);

        return trim($message);
    }
}
