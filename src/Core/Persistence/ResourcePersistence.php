<?php

namespace Monstrex\Ave\Core\Persistence;

use Monstrex\Ave\Core\Resource;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles persistence operations for resources
 */
class ResourcePersistence
{
    protected Resource $resource;

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new model instance
     */
    public function create(array $data): Model
    {
        $model = $this->resource->getModel();
        return $model->create($data);
    }

    /**
     * Update an existing model
     */
    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    /**
     * Delete a model
     */
    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    /**
     * Get model by ID
     */
    public function find($id): ?Model
    {
        $modelClass = $this->resource::$model;
        return $modelClass::find($id);
    }

    /**
     * Get all models
     */
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        $modelClass = $this->resource::$model;
        return $modelClass::all();
    }

    /**
     * Get model count
     */
    public function count(): int
    {
        $modelClass = $this->resource::$model;
        return $modelClass::count();
    }

    /**
     * Create new query builder
     */
    public function query()
    {
        return $this->resource->newQuery();
    }
}
