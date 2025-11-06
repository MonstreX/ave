<?php

namespace Monstrex\Ave\Core;

use InvalidArgumentException;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Core\Components\FormComponent;
use Monstrex\Ave\Core\Components\RowComponent;

class Form
{
    /** @var array<int,RowComponent|FormComponent> */
    protected array $layout = [];

    protected ?string $submitLabel = null;
    protected ?string $cancelUrl = null;

    public static function make(): static
    {
        return new static();
    }

    /**
     * Define form schema using components and/or rows.
     *
     * @param array<int,mixed> $components
     */
    public function schema(array $components): static
    {
        $this->layout = [];

        foreach ($components as $component) {
            $this->layout[] = $this->normalizeComponent($component);
        }

        return $this;
    }

    /**
     * Add single row to form.
     */
    public function addRow(Row $row): static
    {
        $this->layout[] = RowComponent::fromRow($row);

        return $this;
    }

    /**
     * Quick helper: add fields in single column.
     *
     * @param array<int,FormField> $fields
     */
    public function fields(array $fields): static
    {
        $row = Row::make()->columns([
            Col::make(12)->fields($fields),
        ]);

        return $this->addRow($row);
    }

    public function submitLabel(string $label): static
    {
        $this->submitLabel = $label;
        return $this;
    }

    public function cancelUrl(string $url): static
    {
        $this->cancelUrl = $url;
        return $this;
    }

    /**
     * Get normalized layout definition for rendering.
     *
     * @return array<int,array<string,mixed>>
     */
    public function layout(): array
    {
        return array_map(
            static function (RowComponent|FormComponent $component): array {
                if ($component instanceof RowComponent) {
                    return $component->toLayoutArray();
                }

                return [
                    'type' => 'component',
                    'component' => $component,
                ];
            },
            $this->layout
        );
    }

    /**
     * Get all fields (flattened from all components).
     *
     * @return array<int,FormField>
     */
    public function getAllFields(): array
    {
        $fields = [];

        foreach ($this->layout as $component) {
            $fields = array_merge($fields, $component->flattenFields());
        }

        return $fields;
    }

    /**
     * Find field by key.
     */
    public function getField(string $key)
    {
        foreach ($this->getAllFields() as $field) {
            if ($field->key() === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Get all fields for the controller.
     *
     * @return array<int,FormField>
     */
    public function getFields(): array
    {
        return $this->getAllFields();
    }

    /**
     * Get form layout rows for JSON responses (row-only).
     *
     * @return array<int,array<string,mixed>>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->layout() as $item) {
            if (($item['type'] ?? null) === 'row') {
                $rows[] = $item['columns'];
            }
        }

        return $rows;
    }

    public function getSubmitLabel(): string
    {
        return $this->submitLabel ?? 'Save';
    }

    public function getCancelUrl(): ?string
    {
        return $this->cancelUrl;
    }

    /**
     * @param mixed $component
     */
    protected function normalizeComponent(mixed $component): RowComponent|FormComponent
    {
        if ($component instanceof RowComponent) {
            return $component;
        }

        if ($component instanceof Row) {
            return RowComponent::fromRow($component);
        }

        if ($component instanceof FormComponent) {
            return $component;
        }

        if (is_array($component)) {
            $row = Row::make()->columns([
                Col::make(12)->fields($component),
            ]);

            return RowComponent::fromRow($row);
        }

        throw new InvalidArgumentException('Unsupported form schema component.');
    }
}

