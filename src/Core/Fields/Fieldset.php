<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monstrex\Ave\Contracts\HandlesFormRequest;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Contracts\ProvidesValidationRules;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\FieldPersistenceResult;
use Monstrex\Ave\Core\Fields\Fieldset\Item;
use Monstrex\Ave\Core\Fields\Fieldset\ItemFactory;
use Monstrex\Ave\Core\Fields\Fieldset\Renderer;
use Monstrex\Ave\Core\Fields\Fieldset\RequestProcessor;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Core\Validation\FieldValidationRuleExtractor;

/**
 * Fieldset - repeatable group of fields stored within a single JSON column.
 *
 * This is an evolution of the Ave v1 FieldSet with the same behavioural contract:
 * - repeatable items with stable IDs for drag-and-drop
 * - nested media fields that keep deterministic collection names
 * - ability to remove items together with their media payloads
 */
class Fieldset extends AbstractField implements HandlesFormRequest, ProvidesValidationRules, HandlesPersistence
{
    /** @var array<int,AbstractField|Row> */
    protected array $childSchema = [];

    protected bool $sortable = true;
    protected bool $collapsible = false;
    protected bool $collapsed = false;

    protected int $minItems = 0;
    protected ?int $maxItems = null;

    protected string $addButtonLabel = 'Add Item';
    protected string $deleteButtonLabel = 'Delete';
    protected ?string $headTitle = null;
    protected ?string $headPreview = null;

    /** @var array<int,array<string,mixed>> */
    protected array $preparedItems = [];

    /** @var array<int,Item> */
    private array $items = [];

    /** @var array<int,array<string,mixed>> */
    private array $itemInstances = [];

    /** @var array<int,int> */
    private array $itemIds = [];

    /** @var array<int,mixed> */
    private array $templateFields = [];

    private ?Renderer $renderer = null;
    private ?RequestProcessor $requestProcessor = null;
    private ?ItemFactory $itemFactory = null;

    public static function make(string $key): static
    {
        return parent::make($key)
            ->default([])
            ->rules(['array']);
    }

    /**
     * @param  array<int,AbstractField|Row>  $fields
     */
    public function schema(array $fields): static
    {
        $this->childSchema = $fields;

        return $this;
    }

    /**
     * @return array<int,AbstractField|Row>
     */
    public function getChildSchema(): array
    {
        return $this->childSchema;
    }

    /**
     * Get the state path for a specific item within this fieldset.
     *
     * Fieldsets are repeatable, so items have indexed paths like 'items.0', 'items.1', etc.
     * This method builds the full state path for a given item.
     *
     * @param int|string $itemId The item index or ID
     * @return string The state path for this item (e.g., 'items.0')
     */
    public function getItemStatePath(int|string $itemId): string
    {
        return "{$this->getStatePath()}.{$itemId}";
    }

    /**
     * Override getChildStatePath to provide proper parent path for nested fields.
     *
     * When items are rendered, they get a specific item path. Children of items
     * should compose from that path, not the fieldset's own path.
     *
     * Note: This is called during ItemFactory processing, where the item path
     * has already been set on the container fieldset via statePath().
     */
    public function getChildStatePath(): string
    {
        return $this->getStatePath();
    }

    public function prepareRequest(Request $request, FormContext $context): void
    {
        $raw = $request->input($this->key, []);

        if (!is_array($raw)) {
            $request->merge([$this->key => []]);
            return;
        }

        $sanitized = [];
        $usedIds = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = $this->resolveStableItemId($item['_id'] ?? null, $usedIds);

            $item['_id'] = $id;
            $sanitized[] = $item;
        }

        $normalized = array_values($sanitized);

        Log::debug('Fieldset prepareRequest - structure normalized', [
            'field' => $this->key,
            'item_count' => count($normalized),
            'item_ids' => array_column($normalized, '_id'),
        ]);

        $request->merge([$this->key => $normalized]);
    }

    public function buildValidationRules(): array
    {
        $rules = [];

        $baseRules = $this->getRules();
        if (!in_array('array', $baseRules, true)) {
            $baseRules[] = 'array';
        }

        if ($this->minItems > 0) {
            $baseRules[] = 'min:' . $this->minItems;
        }

        if ($this->maxItems !== null) {
            $baseRules[] = 'max:' . $this->maxItems;
        }

        $rules[$this->key()] = $this->formatRulesForValidation($baseRules, $this->isRequired());

        foreach ($this->childSchema as $schemaItem) {
            // Handle Row containers - iterate through columns and fields
            if ($schemaItem instanceof Row) {
                foreach ($schemaItem->getColumns() as $column) {
                    foreach ($column->getFields() as $child) {
                        if (!$child instanceof AbstractField) {
                            continue;
                        }

                        $this->addChildValidationRules($rules, $child);
                    }
                }
                continue;
            }

            // Handle regular AbstractField
            if (!$schemaItem instanceof AbstractField) {
                continue;
            }

            $this->addChildValidationRules($rules, $schemaItem);
        }

        return $rules;
    }

    /**
     * Add validation rules for a child field to the rules array.
     *
     * Handles conversion of field-specific properties (minLength, maxLength, pattern, etc.)
     * to Laravel validation rules using FieldValidationRuleExtractor.
     *
     * @param array<string,string> $rules Reference to the rules array
     * @param AbstractField $child The field to extract rules from
     * @return void
     */
    private function addChildValidationRules(array &$rules, AbstractField $child): void
    {
        $childRules = $child->getRules();

        // Extract field-specific validation rules (minLength, maxLength, pattern, min, max)
        // using FieldValidationRuleExtractor for consistency with FormValidator
        $childRules = FieldValidationRuleExtractor::extract($child, $childRules);

        if (empty($childRules) && !$child->isRequired()) {
            return;
        }

        $rules[sprintf('%s.*.%s', $this->key(), $child->key())] = $this->formatRulesForValidation(
            $childRules,
            $child->isRequired()
        );
    }

    public function prepareForSave(mixed $value, Request $request, FormContext $context): FieldPersistenceResult
    {
        $this->preparedItems = [];

        // Get original value from database through DataSource abstraction
        $originalValue = null;
        $dataSource = $context->dataSource();
        if ($dataSource) {
            $originalValue = $dataSource->get($this->key);
            // Decode if stored as JSON
            if (is_string($originalValue)) {
                $decoded = json_decode($originalValue, true);
                if (is_array($decoded)) {
                    $originalValue = $decoded;
                }
            }
        }

        // Pass the original value (from database) to processor so it can detect deleted items
        $result = $this->requestProcessor()->process($request, $context, $originalValue);
        $this->preparedItems = $result->items();

        return FieldPersistenceResult::make($this->preparedItems, $result->deferredActions());
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;
        if ($collapsed) {
            $this->collapsible = true;
        }

        return $this;
    }

    public function minItems(int $min): static
    {
        $this->minItems = $min;

        return $this;
    }

    public function maxItems(int $max): static
    {
        $this->maxItems = $max;

        return $this;
    }

    public function getMinItems(): int
    {
        return $this->minItems;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function addButtonLabel(string $label): static
    {
        $this->addButtonLabel = $label;

        return $this;
    }

    public function deleteButtonLabel(string $label): static
    {
        $this->deleteButtonLabel = $label;

        return $this;
    }

    public function headTitle(string $fieldName): static
    {
        $this->headTitle = $fieldName;

        return $this;
    }

    public function headPreview(string $fieldName): static
    {
        $this->headPreview = $fieldName;

        return $this;
    }

    public function prepareForDisplay(FormContext $context): void
    {
        $result = $this->renderer()->render($this, $context);

        $this->items = $result->items();
        $this->itemIds = $result->itemIds();

        $this->itemInstances = array_map(
            static fn (Item $item): array => [
                'id' => $item->id,
                'index' => $item->index,
                'fields' => $item->fields,
                'data' => $item->data,
                'context' => $item->context,
            ],
            $this->items
        );

        $this->templateFields = $result->templateFields();
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        $source->set($this->key, $this->preparedItems ?: []);
    }

    public function extract(mixed $raw): mixed
    {
        if (!empty($this->preparedItems)) {
            return $this->preparedItems;
        }

        return $this->normalizeRawItems($raw);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => $this->type(),
            'childSchema' => $this->childSchema,
            'itemInstances' => $this->itemInstances,
            'itemIds' => $this->itemIds,
            'sortable' => $this->sortable,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'minItems' => $this->minItems,
            'maxItems' => $this->maxItems,
            'addButtonLabel' => $this->addButtonLabel,
            'deleteButtonLabel' => $this->deleteButtonLabel,
            'headTitle' => $this->headTitle,
            'headPreview' => $this->headPreview,
        ]);
    }

    public function getItemFields(int $index): array
    {
        return $this->itemInstances[$index]['fields'] ?? [];
    }

    public function getItemId(int $index): string
    {
        return $this->itemInstances[$index]['id'] ?? (string) $index;
    }

    public function getRowTitle(int $index): string
    {
        $data = $this->itemInstances[$index]['data'] ?? [];

        if ($this->headTitle) {
            return (string) ($data[$this->headTitle] ?? 'Item ' . ($index + 1));
        }

        return 'Item ' . ($index + 1);
    }

    public function render(FormContext $context): string
    {
        if (empty($this->itemInstances) && !empty($this->childSchema)) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?? $this->resolveDefaultView();

        return view($view, [
            'field' => $this,
            'context' => $context,
            'hasError' => $context->hasError($this->key),
            'errors' => $context->getErrors($this->key),
            'attributes' => '',
            ...$this->toArray(),
        ])->render();
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function getAddButtonLabel(): string
    {
        return $this->addButtonLabel;
    }

    public function getDeleteButtonLabel(): string
    {
        return $this->deleteButtonLabel;
    }

    public function getHeadTitle(): ?string
    {
        return $this->headTitle;
    }

    public function getHeadPreview(): ?string
    {
        return $this->headPreview;
    }

    /**
     * @return array<int,mixed>
     */
    public function prepareTemplateFields(): array
    {
        if (empty($this->templateFields)) {
            $this->templateFields = $this->itemFactory()->makeTemplateFields();
        }

        return $this->templateFields;
    }

    /**
     * Execute queued media operations once the record is persisted.
     */
    /**
     * Normalize validation rule set taking required/nullable into account.
     */
    private function formatRulesForValidation(array $rules, bool $required): string
    {
        $rules = array_values(array_filter($rules));

        if ($required) {
            if (!in_array('required', $rules, true)) {
                array_unshift($rules, 'required');
            }
        } elseif (!in_array('nullable', $rules, true)) {
            array_unshift($rules, 'nullable');
        }

        return implode('|', $rules);
    }

    /**
     * Resolve stable numeric identifier for a fieldset item.
     *
     * @param array<int,bool> $usedIds
     */
    private function resolveStableItemId(mixed $identifier, array &$usedIds): int
    {
        if (is_numeric($identifier)) {
            $id = (int) $identifier;
        } elseif (is_string($identifier) && ctype_digit($identifier)) {
            $id = (int) $identifier;
        } else {
            $id = $this->nextAvailableIndex($usedIds);
        }

        $usedIds[$id] = true;

        return $id;
    }

    /**
     * Helper reused across request processing for checking empty values.
     */
    public function valueIsMeaningful(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty($value);
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }

    /**
     * @return array<int,mixed>
     */
    private function normalizeRawItems(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        $usedIds = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = $this->resolveStableItemId($item['_id'] ?? null, $usedIds);
            $item['_id'] = $id;
            $normalized[] = $item;
        }

        return array_values($normalized);
    }

    /**
     * Fieldset manages its children internally, so it reports only itself.
     *
     * @return array<int,$this>
     */
    public function flattenFields(): array
    {
        return [$this];
    }

    private function renderer(): Renderer
    {
        return $this->renderer ??= new Renderer($this->itemFactory());
    }

    private function requestProcessor(): RequestProcessor
    {
        return $this->requestProcessor ??= new RequestProcessor($this);
    }

    private function itemFactory(): ItemFactory
    {
        return $this->itemFactory ??= new ItemFactory($this);
    }

    private function nextAvailableIndex(array $usedIds): int
    {
        $candidate = 0;

        while (isset($usedIds[$candidate])) {
            $candidate++;
        }

        return $candidate;
    }
}

