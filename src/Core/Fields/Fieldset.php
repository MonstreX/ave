<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\Forms\FormContext;

class Fieldset extends AbstractField
{
    /** @var array<AbstractField> */
    protected array $childSchema = [];

    protected array $itemInstances = [];

    protected array $itemIds = [];

    protected bool $sortable = true;

    protected bool $collapsible = false;

    protected bool $collapsed = false;

    protected ?int $minItems = null;

    protected ?int $maxItems = null;

    protected string $addButtonLabel = 'Add item';

    protected string $rowTitleTemplate = '';

    protected string $containerClass = '';

    public static function make(string $key): static
    {
        return parent::make($key)
            ->default([])
            ->rules(['array']);
    }

    /**
     * @param array<AbstractField> $fields
     */
    public function schema(array $fields): static
    {
        $this->childSchema = $fields;

        return $this;
    }

    /**
     * @return array<AbstractField>
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

    public function minItems(?int $min): static
    {
        $this->minItems = $min;

        return $this;
    }

    public function maxItems(?int $max): static
    {
        $this->maxItems = $max;

        return $this;
    }

    public function getMinItems(): ?int
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

    public function rowTitleTemplate(string $template): static
    {
        $this->rowTitleTemplate = $template;

        return $this;
    }

    public function containerClass(string $class): static
    {
        $this->containerClass = $class;

        return $this;
    }

    public function prepareForDisplay(FormContext $context): void
    {
        $dataSource = $context->dataSource();
        $items = $dataSource ? $dataSource->get($this->key) : [];

        if (!is_array($items)) {
            $items = [];
        }

        $this->itemInstances = [];
        $this->itemIds = [];

        foreach (array_values($items) as $index => $item) {
            $itemData = is_array($item) ? $item : [];
            $itemDataSource = new ArrayDataSource($itemData);
            $itemFields = [];
            $itemId = Str::random(8);

            foreach ($this->childSchema as $fieldDefinition) {
                $field = clone $fieldDefinition;
                $field->setKey(sprintf('%s.%d.%s', $this->key, $index, $field->getKey()));
                $field->fillFromDataSource($itemDataSource);

                if (method_exists($field, 'prepareForDisplay')) {
                    $field->prepareForDisplay(FormContext::forData($itemData));
                }

                $itemFields[] = $field;
            }

            $this->itemIds[$index] = $itemId;
            $this->itemInstances[$index] = [
                'id' => $itemId,
                'index' => $index,
                'fields' => $itemFields,
                'data' => $itemData,
            ];
        }
    }

    public function beforeApply(Request $request, FormContext $context): void
    {
        $submitted = $request->input($this->key, []);

        if (!is_array($submitted)) {
            $submitted = [];
        }

        $cleaned = [];

        foreach ($submitted as $item) {
            if (is_array($item)) {
                $cleaned[] = $item;
            }
        }

        $this->setValue($cleaned);
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        $source->set($this->key, is_array($value) ? $value : []);
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
            'rowTitleTemplate' => $this->rowTitleTemplate,
            'containerClass' => $this->containerClass,
        ]);
    }

    public function getItemFields(int $index): array
    {
        return $this->itemInstances[$index]['fields'] ?? [];
    }

    public function getItemId(int $index): string
    {
        return $this->itemIds[$index] ?? ('item-' . $index);
    }

    public function getRowTitle(int $index): string
    {
        $data = $this->itemInstances[$index]['data'] ?? [];

        if ($this->rowTitleTemplate === '') {
            return 'Item ' . ($index + 1);
        }

        $title = str_replace('{index}', (string) ($index + 1), $this->rowTitleTemplate);

        foreach ($data as $key => $value) {
            $title = str_replace('{' . $key . '}', (string) $value, $title);
        }

        return $title;
    }

    public function render(FormContext $context): string
    {
        if (empty($this->itemInstances) && !empty($this->childSchema)) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.fieldset';
        $errors = $context->getErrors($this->key);

        return view($view, [
            'field' => $this,
            'context' => $context,
            'hasError' => !empty($errors),
            'errors' => $errors,
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

    public function getContainerClass(): string
    {
        return $this->containerClass;
    }
}
