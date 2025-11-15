<?php

namespace Monstrex\Ave\Core;

use InvalidArgumentException;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Core\Components\FormComponent;

class Form
{
    /** @var array<int,FormComponent> */
    protected array $layout = [];

    /** @var array<int,FormField> */
    protected array $fields = [];

    protected ?string $submitLabel = null;
    protected ?string $cancelUrl = null;

    public static function make(): static
    {
        return new static();
    }

    /**
     * Define form schema using components and/or fields.
     *
     * Supports mixed content:
     * - FormComponent instances (Div, Row, Col, Tabs, Panel, etc.)
     * - FormField instances (TextInput, Textarea, Number, etc.)
     *
     * @param array<int,FormComponent|FormField> $components
     */
    public function schema(array $components): static
    {
        $this->layout = [];
        $this->fields = [];

        foreach ($components as $component) {
            // Handle FormField (direct fields)
            if ($component instanceof FormField) {
                $this->fields[] = $component;
                continue;
            }

            // Handle FormComponent (layout containers)
            if ($component instanceof FormComponent) {
                $this->layout[] = $component;
                continue;
            }

            throw new InvalidArgumentException(
                sprintf('Invalid schema component: %s. Must be FormComponent or FormField.',
                    is_object($component) ? get_class($component) : gettype($component)
                )
            );
        }

        return $this;
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
     * Includes both FormComponent instances and direct FormField instances.
     * Direct fields are wrapped in a simple Div container for consistent rendering.
     *
     * @return array<int,array<string,mixed>>
     */
    public function layout(): array
    {
        $result = [];

        // Add direct fields first (if any) - wrap them in a Div for rendering
        if (!empty($this->fields)) {
            $result[] = [
                'type' => 'component',
                'component' => \Monstrex\Ave\Core\Components\Div::make()->schema($this->fields),
            ];
        }

        // Add layout components
        foreach ($this->layout as $component) {
            $result[] = [
                'type' => 'component',
                'component' => $component,
            ];
        }

        return $result;
    }

    /**
     * Get all fields (direct fields + flattened from all components).
     *
     * @return array<int,FormField>
     */
    public function getAllFields(): array
    {
        $fields = $this->fields; // Start with direct fields

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

}
