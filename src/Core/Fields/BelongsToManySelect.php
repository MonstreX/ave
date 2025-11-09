<?php

namespace Monstrex\Ave\Core\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Fields\Concerns\HasRelationQueryModifiers;
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
class BelongsToManySelect extends AbstractField implements HandlesPersistence
{
    use HasRelationQueryModifiers;
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
                return $this->value->map(fn($m) => $m->getKey())->toArray();
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
            // Explicitly qualify the id column to avoid ambiguity when joining tables
            $relation = $model->{$this->relationship}();
            $relatedTable = $relation->getRelated()->getTable();

            $relatedIds = $model->{$this->relationship}()
                ->pluck($relatedTable . '.' . $relation->getRelated()->getKeyName())
                ->toArray();

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
     * Prepare field for save using deferred action.
     * This is the new universal way to handle relation fields.
     * The actual sync() is executed AFTER the model is saved.
     */
    public function prepareForSave(mixed $value, Request $request, FormContext $context): FieldPersistenceResult
    {
        $relationKey = $this->key;
        $relationName = $this->relationship;

        // Create deferred action that will execute AFTER model is saved
        $deferred = [
            function (Model $savedRecord) use ($relationKey, $relationName, $value): void {
                // Only sync if model exists (has ID), relation exists, and value is provided
                if ($relationName && method_exists($savedRecord, $relationName) && $savedRecord->exists) {
                    $syncIds = is_array($value) ? $value : [];
                    $savedRecord->{$relationName}()->sync($syncIds);
                }
            },
        ];

        // Return null for payload (don't add to fillable data) + deferred action
        return FieldPersistenceResult::make(null, $deferred);
    }

    /**
     * Apply posted value to the data source.
     * Uses sync() for BelongsToMany relations.
     * @deprecated Use prepareForSave() instead for better integration
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        // For BelongsToMany, we use sync() to update pivot table
        $source->sync($this->key, $value ?: []);
    }
}
