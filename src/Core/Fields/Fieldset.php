<?php

namespace Monstrex\Ave\Core\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\Fieldset\Item;
use Monstrex\Ave\Core\Fields\Fieldset\ItemFactory;
use Monstrex\Ave\Core\Fields\Fieldset\MediaManager;
use Monstrex\Ave\Core\Fields\Fieldset\Renderer;
use Monstrex\Ave\Core\Fields\Fieldset\RequestProcessor;

/**
 * Fieldset - repeatable group of fields stored within a single JSON column.
 *
 * This is an evolution of the Ave v1 FieldSet with the same behavioural contract:
 * - repeatable items with stable IDs for drag-and-drop
 * - nested media fields that keep deterministic collection names
 * - ability to remove items together with their media payloads
 */
class Fieldset extends AbstractField
{
    /** @var array<int,AbstractField> */
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

    /** @var array<int,Closure> */
    private array $deferredActions = [];

    private ?Renderer $renderer = null;
    private ?RequestProcessor $requestProcessor = null;
    private ?ItemFactory $itemFactory = null;
    private ?MediaManager $mediaManager = null;

    public static function make(string $key): static
    {
        return parent::make($key)
            ->default([])
            ->rules(['array']);
    }

    /**
     * @param  array<int,AbstractField>  $fields
     */
    public function schema(array $fields): static
    {
        $this->childSchema = $fields;

        return $this;
    }

    /**
     * @return array<int,AbstractField>
     */
    public function getChildSchema(): array
    {
        return $this->childSchema;
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
                'id' => 'item-' . $item->id,
                'index' => $item->index,
                'fields' => $item->fields,
                'data' => $item->data,
            ],
            $this->items
        );

        $this->templateFields = $result->templateFields();
    }

    public function beforeApply(Request $request, FormContext $context): void
    {
        $this->preparedItems = [];
        $this->deferredActions = [];

        $result = $this->requestProcessor()->process($request, $context);

        $this->preparedItems = $result->items();
        $this->deferredActions = $result->deferredActions();
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
            'type' => 'fieldset',
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
        return $this->itemInstances[$index]['id'] ?? ('item-' . $index);
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

        $view = $this->view ?? 'ave::components.forms.fieldset';

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
    public function runDeferredActions(Model $record): void
    {
        foreach ($this->deferredActions as $action) {
            $action($record);
        }

        $this->deferredActions = [];
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

        return array_values(array_filter($raw, 'is_array'));
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
        return $this->requestProcessor ??= new RequestProcessor(
            $this,
            $this->mediaManager()
        );
    }

    private function itemFactory(): ItemFactory
    {
        return $this->itemFactory ??= new ItemFactory($this);
    }

    private function mediaManager(): MediaManager
    {
        return $this->mediaManager ??= new MediaManager();
    }
}
