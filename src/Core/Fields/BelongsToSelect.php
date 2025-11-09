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
use Monstrex\Ave\Exceptions\HierarchicalRelationException;

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
class BelongsToSelect extends AbstractField implements HandlesPersistence
{
    use HasRelationQueryModifiers;
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

    /** Whether to display options as hierarchical tree (parent-child). */
    protected bool $isHierarchical = false;

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


    /** Allow null (no related record). */
    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;
        if ($nullable && !in_array('nullable', $this->rules, true)) {
            $this->rules[] = 'nullable';
        }
        return $this;
    }

    /**
     * Enable hierarchical (tree) mode for options.
     *
     * Related model must have:
     * - parent_id column (nullable integer)
     * - order column (integer)
     *
     * Options will be displayed as nested tree with indentation.
     *
     * @throws HierarchicalRelationException if model lacks required columns
     */
    public function hierarchical(bool $enabled = true): static
    {
        $this->isHierarchical = $enabled;
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

            if ($this->isHierarchical) {
                return $this->optionsCache = $this->resolveHierarchicalOptions($related);
            }

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
     * Resolve options as hierarchical tree (parent-child structure).
     *
     * @param Model $relatedModel
     * @return Collection Collection of models in tree order with hierarchy info
     * @throws HierarchicalRelationException if required columns are missing
     */
    protected function resolveHierarchicalOptions(Model $relatedModel): Collection
    {
        // Validate required columns
        $table = $relatedModel->getTable();
        $columns = \DB::getSchemaBuilder()->getColumnListing($table);

        $hasMissing = [];
        if (!in_array('parent_id', $columns)) {
            $hasMissing[] = 'parent_id';
        }
        if (!in_array('order', $columns)) {
            $hasMissing[] = 'order';
        }

        if (!empty($hasMissing)) {
            if (count($hasMissing) === 2) {
                throw HierarchicalRelationException::missingBothColumns(get_class($relatedModel));
            } elseif (in_array('parent_id', $hasMissing)) {
                throw HierarchicalRelationException::missingParentIdColumn(get_class($relatedModel));
            } else {
                throw HierarchicalRelationException::missingOrderColumn(get_class($relatedModel));
            }
        }

        // Load ALL items from related table
        $query = $relatedModel->newQuery();

        if ($this->modifyQuery) {
            $query = ($this->modifyQuery)($query);
        }

        // Load all items and index by ID for quick lookup
        $allItems = $query->orderBy('order')->get();
        $itemsById = $allItems->keyBy('id');

        // Build tree by finding root items and recursively adding children
        $tree = collect();
        foreach ($allItems as $item) {
            // Only process items without parent (or parent not in collection)
            if (is_null($item->parent_id) || !$itemsById->has($item->parent_id)) {
                $this->buildHierarchicalTree($item, $tree, $itemsById, 0);
            }
        }

        return $tree;
    }

    /**
     * Recursively build hierarchical tree of options.
     *
     * @param Model $item Current item
     * @param Collection $tree Collection to add item to
     * @param Collection $itemsById All items indexed by ID
     * @param int $depth Depth level (for indentation)
     */
    private function buildHierarchicalTree(Model $item, Collection $tree, Collection $itemsById, int $depth = 0): void
    {
        // Add current item with depth info
        $item->_hierarchy_depth = $depth;

        // Use non-breaking spaces for indentation
        // \u00A0 is invisible but doesn't collapse in HTML
        // Each level gets 4 non-breaking spaces
        $item->_hierarchy_indent = str_repeat("\u{00A0}", $depth * 4);

        $tree->push($item);

        // Find and add children from the collection
        $children = $itemsById->filter(fn($child) => $child->parent_id === $item->getKey());

        foreach ($children as $child) {
            $this->buildHierarchicalTree($child, $tree, $itemsById, $depth + 1);
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
            'hierarchical' => $this->isHierarchical,
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
            $label = $m->getAttribute($this->labelColumn);

            // Add indentation for hierarchical options
            if ($this->isHierarchical && isset($m->_hierarchy_indent)) {
                $label = $m->_hierarchy_indent . $label;
            }

            return [
                'value' => $m->getKey(),
                'label' => $label,
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

    /**
     * Prepare field for save.
     * For BelongsTo, the FK value is returned directly (no deferred actions needed).
     */
    public function prepareForSave(mixed $value, Request $request, FormContext $context): FieldPersistenceResult
    {
        // For BelongsTo, the value is just the foreign key ID
        // No deferred actions needed - it gets written directly to the model
        return FieldPersistenceResult::make($value ?: null, []);
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        // For BelongsTo, we write the FK directly to the model
        $source->set($this->key, $value ?: null);
    }
}
