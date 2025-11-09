<?php

namespace Monstrex\Ave\Core\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\FormContext;

/**
 * BelongsToSelect Field
 *
 * Renders a <select> where options come from a BelongsTo relation.
 * Works with foreign key column (e.g. category_id).
 *
 * Example:
 *   BelongsToSelect::make('category_id')
 *       ->label('Category')
 *       ->relationship('category', 'title')
 *       ->nullable()
 *       ->searchable()
 *
 * Features:
 * - Automatically loads options from related model
 * - Supports custom query modification
 * - Supports nullable relations
 * - Caches options for performance
 */
class BelongsToSelect extends AbstractField
{
    /** Eloquent relation name on the model (e.g. "category"). */
    protected ?string $relationship = null;

    /** Column used as label for related model (e.g. "title", "name"). */
    protected string $labelColumn = 'title';

    /** Optional query modifier for options. */
    protected ?Closure $modifyQuery = null;

    /** Mark field as searchable (for async loading or client-side). */
    protected bool $searchable = false;

    /** Limit options for eager loading (for small dictionaries). */
    protected int $optionsLimit = 100;

    /** Whether the relation can be null. */
    protected bool $nullable = false;

    /** Cached options collection for this field. */
    protected ?Collection $optionsCache = null;

    /**
     * Bind this field to a BelongsTo relation.
     *
     * @param string $relationship Eloquent relation name (e.g. "category").
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

    /** Allow null (no related record). */
    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;
        if ($nullable && !in_array('nullable', $this->rules, true)) {
            $this->rules[] = 'nullable';
        }
        return $this;
    }

    /** Resolve options from relation. */
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

    /** Prepare value from data source before rendering. */
    public function prepareForDisplay(FormContext $context): void
    {
        // For BelongsTo we store the foreign key value
        $this->fillFromDataSource($context->dataSource());
    }

    /** Transform field to array for Blade. */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'relationship' => $this->relationship,
            'labelColumn' => $this->labelColumn,
            'searchable' => $this->searchable,
            'nullable' => $this->nullable,
        ]);
    }

    /** Render field via Blade view. */
    public function render(FormContext $context): string
    {
        if (is_null($this->getValue())) {
            $this->prepareForDisplay($context);
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
            'value' => $this->getValue(),
            ...$field,
        ])->render();
    }

    /**
     * Apply posted value to the data source.
     * The FK is written directly to the model.
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        // For BelongsTo, we write the FK directly to the model
        $source->set($this->key, $value ?: null);
    }
}
