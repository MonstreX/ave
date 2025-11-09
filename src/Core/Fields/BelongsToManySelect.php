<?php

namespace Monstrex\Ave\Core\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\FormContext;

/**
 * BelongsToManySelect Field
 *
 * Renders a multi-select <select multiple> where options come from a BelongsToMany relation.
 * Works with pivot table (e.g. article_tag).
 *
 * Example:
 *   BelongsToManySelect::make('tags')
 *       ->label('Tags')
 *       ->relationship('tags', 'name')
 *       ->searchable()
 *
 * Features:
 * - Automatically loads options from related model
 * - Supports custom query modification
 * - Supports filtering (where, orderBy, active, inactive)
 * - Uses sync() for saving (handles pivot table automatically)
 */
class BelongsToManySelect extends AbstractField
{
    /** Eloquent relation name on the model (e.g. "tags"). */
    protected ?string $relationship = null;

    /** Column used as label for related model (e.g. "name", "title"). */
    protected string $labelColumn = 'name';

    /** Optional query modifier for options. */
    protected ?Closure $modifyQuery = null;

    /** Mark field as searchable (for async loading or client-side). */
    protected bool $searchable = false;

    /** Limit options for eager loading (for small dictionaries). */
    protected int $optionsLimit = 500;

    /** Cached options collection for this field. */
    protected ?Collection $optionsCache = null;

    /**
     * Bind this field to a BelongsToMany relation.
     *
     * @param string $relationship Eloquent relation name (e.g. "tags").
     * @param string $labelColumn  Column used as display label (e.g. "name").
     */
    public function relationship(string $relationship, string $labelColumn = 'name'): static
    {
        $this->relationship = $relationship;
        $this->labelColumn  = $labelColumn;

        return $this;
    }

    /** Mark field as searchable (UI will handle search). */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    /** Set options limit for eager-loaded options. */
    public function optionsLimit(int $limit): static
    {
        $this->optionsLimit = max(1, $limit);
        return $this;
    }

    /**
     * Modify the underlying options query.
     *
     * @param Closure $callback fn(Builder $query): Builder
     */
    public function modifyQuery(Closure $callback): static
    {
        $this->modifyQuery = $callback;
        return $this;
    }

    /**
     * Add WHERE condition to options query.
     *
     * @param string $column Column name
     * @param mixed $operator Operator or value (if only 2 args passed)
     * @param mixed $value Value (optional)
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        $previousCallback = $this->modifyQuery;

        $this->modifyQuery = function($query) use ($column, $operator, $value, $previousCallback) {
            if ($previousCallback) {
                $query = $previousCallback($query);
            }

            // Handle different argument combinations
            if ($value === null && $operator !== null) {
                // Two arguments: where('status', true)
                return $query->where($column, $operator);
            }

            // Three arguments: where('status', '=', true)
            return $query->where($column, $operator, $value);
        };

        return $this;
    }

    /**
     * Add ORDER BY to options query.
     *
     * @param string $column Column name
     * @param string $direction 'asc' or 'desc'
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $previousCallback = $this->modifyQuery;

        $this->modifyQuery = function($query) use ($column, $direction, $previousCallback) {
            if ($previousCallback) {
                $query = $previousCallback($query);
            }

            return $query->orderBy($column, $direction);
        };

        return $this;
    }

    /**
     * Filter for 'active' records (where status = true).
     * Assumes 'status' column exists on related model.
     */
    public function active(): static
    {
        return $this->where('status', true);
    }

    /**
     * Filter for 'inactive' records (where status = false).
     */
    public function inactive(): static
    {
        return $this->where('status', false);
    }

    /** Resolve available options from relation. */
    protected function resolveOptions(FormContext $context): Collection
    {
        if ($this->optionsCache !== null) {
            return $this->optionsCache;
        }

        $dataSource = $context->dataSource();
        $model = $dataSource->getModel();

        if (!$model instanceof Model) {
            return $this->optionsCache = collect();
        }

        if (!$this->relationship || !method_exists($model, $this->relationship)) {
            return $this->optionsCache = collect();
        }

        try {
            $relation = $model->{$this->relationship}();
            $related = $relation->getRelated();

            $query = $related->newQuery();

            if ($this->modifyQuery) {
                $query = ($this->modifyQuery)($query);
            }

            $items = $query
                ->orderBy($this->labelColumn)
                ->limit($this->optionsLimit)
                ->get();

            return $this->optionsCache = $items;
        } catch (\Exception $e) {
            return $this->optionsCache = collect();
        }
    }

    /**
     * Get selected option IDs for this field.
     * Returns array of related model IDs.
     */
    public function getValue(): ?array
    {
        // For many-to-many, value can be array of IDs, Collection, or null
        if ($this->value !== null) {
            if (is_array($this->value)) {
                return $this->value;
            }
            if ($this->value instanceof Collection) {
                return $this->value->pluck('id')->toArray();
            }
        }

        return null;
    }

    /** Prepare value from data source before rendering. */
    public function prepareForDisplay(FormContext $context): void
    {
        // First, try to fill from the data source (in case the relation was pre-loaded)
        $this->fillFromDataSource($context->dataSource());

        // If value is not loaded or is not an array of IDs, load it from the relation
        $value = $this->getValue();
        if (empty($value) || !is_array($value)) {
            $this->loadRelatedIds($context->dataSource());
        }
    }

    /**
     * Load related model IDs from the BelongsToMany relation
     */
    protected function loadRelatedIds(DataSourceInterface $source): void
    {
        // Get the underlying model
        if (!method_exists($source, 'getModel')) {
            $this->value = null;
            return;
        }

        $model = $source->getModel();

        if (!$model instanceof Model || !$this->relationship || !method_exists($model, $this->relationship)) {
            $this->value = null;
            return;
        }

        try {
            // Load the related model IDs via the relation
            $relatedIds = $model->{$this->relationship}()->pluck('id')->toArray();
            $this->value = !empty($relatedIds) ? $relatedIds : null;
        } catch (\Exception $e) {
            $this->value = null;
        }
    }

    /** Transform field to array for Blade. */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'relationship' => $this->relationship,
            'labelColumn' => $this->labelColumn,
            'searchable' => $this->searchable,
        ]);
    }

    /** Render field via Blade view. */
    public function render(FormContext $context): string
    {
        // Load value if not already loaded
        $value = $this->getValue();
        if (empty($value)) {
            $this->prepareForDisplay($context);
            $value = $this->getValue();
        }

        $view = $this->view ?: $this->resolveDefaultView();

        $hasError = $context->hasError($this->key);
        $errors = $context->getErrors($this->key);
        $field = $this->toArray();

        $options = $this->resolveOptions($context)->map(function (Model $m) {
            return [
                'value' => $m->getKey(),
                'label' => $m->getAttribute($this->labelColumn),
            ];
        })->values()->all();

        return view($view, [
            'field' => $this,
            'context' => $context,
            'hasError' => $hasError,
            'errors' => $errors,
            'options' => $options,
            'attributes' => '',
            'key' => $this->key,
            'value' => $value ?: [],
            ...$field,
        ])->render();
    }

    /**
     * Apply posted value to the data source.
     * Uses sync() for BelongsToMany relations.
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        // For BelongsToMany, we use sync() to update pivot table
        $source->sync($this->key, $value ?: []);
    }
}
