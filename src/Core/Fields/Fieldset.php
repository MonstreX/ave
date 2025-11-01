<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\Forms\FormContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Fieldset extends AbstractField
{
    protected array $childSchema = [];
    protected array $itemInstances = [];
    protected array $itemIds = [];
    protected bool $sortable = true;
    protected bool $collapsible = false;
    protected bool $collapsed = false;
    protected int $minItems = 0;
    protected int $maxItems = 999;
    protected string $addButtonLabel = 'Добавить';
    protected string $rowTitleTemplate = '';
    protected string $containerClass = '';

    public static function make(string $key): static
    {
        return parent::make($key)->default([]);
    }

    public function schema(array $fields): static
    {
        $this->childSchema = $fields;
        return $this;
    }

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

    /**
     * Prepare for display - create field instances for each item
     * Important: Create item-specific context for nested fields like Media, CodeEditor, RichEditor
     */
    public function prepareForDisplay(FormContext $context): void
    {
        $dataSource = $context->dataSource();
        $itemsData = $dataSource->get($this->key) ?? [];

        if (!is_array($itemsData)) {
            $itemsData = [];
        }

        $this->itemInstances = [];
        $this->itemIds = [];

        foreach ($itemsData as $index => $itemData) {
            $itemDataSource = new ArrayDataSource($itemData);
            $itemFields = [];
            $itemId = Str::random(8);

            $this->itemIds[$index] = $itemId;

            foreach ($this->childSchema as $fieldDefinition) {
                $field = clone $fieldDefinition;
                $field->setKey("{$this->key}.{$index}." . $field->getKey());
                $field->fillFromDataSource($itemDataSource);

                // Create item-specific context for nested fields
                // This allows Media, CodeEditor, RichEditor and other complex fields to work correctly
                if (method_exists($field, 'prepareForDisplay')) {
                    $itemContext = FormContext::forData($itemData);
                    if ($context->getOldInput()) {
                        $itemContext->withOldInput($context->getOldInput());
                    }
                    // Pass errors if available
                    if (method_exists($context, 'getErrors')) {
                        $itemErrors = $context->getErrors($field->getKey());
                        if (!empty($itemErrors)) {
                            $itemContext->withErrors([$field->getKey() => $itemErrors]);
                        }
                    }

                    $field->prepareForDisplay($itemContext);
                }

                $itemFields[] = $field;
            }

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
        $allValues = $request->all();
        $keyPrefix = $this->key . '.';
        $items = [];
        $indices = [];

        foreach ($allValues as $key => $value) {
            if (strpos($key, $keyPrefix) === 0) {
                $remainder = substr($key, strlen($keyPrefix));
                if (preg_match('/^(\d+)\./', $remainder, $matches)) {
                    $index = (int)$matches[1];
                    $indices[$index] = true;
                }
            }
        }

        foreach (array_keys($indices) as $index) {
            $itemPrefix = $this->key . '.' . $index . '.';
            $itemData = [];

            foreach ($allValues as $key => $value) {
                if (strpos($key, $itemPrefix) === 0) {
                    $fieldKey = substr($key, strlen($itemPrefix));
                    $itemData[$fieldKey] = $value;
                }
            }

            if (!empty($itemData)) {
                $items[$index] = $itemData;
            }
        }

        $items = array_values($items);
        $this->setValue($items);
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        if (!is_array($value)) {
            $value = [];
        }

        $cleanedItems = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $cleanedItems[] = $item;
            }
        }

        $source->set($this->key, $cleanedItems);
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
        return $this->itemIds[$index] ?? 'item-' . $index;
    }

    public function getRowTitle(int $index): string
    {
        if (empty($this->rowTitleTemplate)) {
            return "Элемент " . ($index + 1);
        }

        $itemData = $this->itemInstances[$index]['data'] ?? [];
        $title = $this->rowTitleTemplate;
        $title = str_replace('{index}', (string)($index + 1), $title);

        foreach ($itemData as $key => $value) {
            $title = str_replace('{' . $key . '}', (string)$value, $title);
        }

        return $title;
    }

    public function render(FormContext $context): string
    {
        if (empty($this->itemInstances) && empty($this->childSchema) === false) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?: 'ave::components.forms.fieldset';

        return view($view, [
            'field' => $this,
            'context
