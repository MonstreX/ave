<?php

namespace Monstrex\Ave\Core\DataSources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Data source wrapper for Eloquent models
 *
 * This class maintains backward compatibility with existing form fields
 * by wrapping the Model and providing DataSource interface.
 *
 * Allows fields to work with model attributes using dot notation,
 * including relationship access (e.g., 'category.name').
 */
class ModelDataSource implements DataSourceInterface
{
    public function __construct(
        private Model $model
    ) {}

    /**
     * Get value from model using dot notation
     *
     * Uses Laravel's data_get() helper to support dot notation
     * and relationships (e.g., 'category.name')
     *
     * @param  string  $key  Key in dot notation
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return data_get($this->model, $key);
    }

    /**
     * Set value on model
     *
     * Directly sets the attribute on the model.
     * For nested keys (e.g., 'items.0.title'), only the first segment is used
     * (nested setting is handled by ArrayDataSource).
     *
     * @param  string  $key  Key in dot notation
     * @param  mixed  $value  Value to set
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        // For simple keys, set directly on model
        // For dot notation, only set the first segment
        // (nested setting is handled by ArrayDataSource)
        $firstKey = explode('.', $key)[0];
        $this->model->{$firstKey} = $value;
    }

    /**
     * Check if attribute exists on model
     *
     * @param  string  $key  Key in dot notation
     * @return bool
     */
    public function has(string $key): bool
    {
        $value = $this->get($key);

        return $value !== null;
    }

    /**
     * Get model attributes as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->model->toArray();
    }

    /**
     * Get the underlying model
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Sync a relation (for BelongsToMany relations)
     *
     * @param  string  $relation  Relation name
     * @param  array  $ids  Array of related model IDs
     * @return void
     */
    public function sync(string $relation, array $ids): void
    {
        if (method_exists($this->model, $relation)) {
            try {
                $this->model->{$relation}()->sync($ids);
            } catch (\Exception $e) {
                // Log but continue if relation doesn't support sync or other errors occur
                Log::warning('Failed to sync relation', [
                    'model' => get_class($this->model),
                    'relation' => $relation,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
