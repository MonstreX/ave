<?php

namespace Monstrex\Ave\Core\Components\Concerns;

use InvalidArgumentException;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Core\Components\FormComponent;

/**
 * Trait for components that contain child components and/or fields
 *
 * Used by container components like Tabs, Group, Div
 * Supports both FormComponent (layout containers) and AbstractField (form fields)
 */
trait HasComponents
{
    /**
     * @var array<int,FormComponent>
     */
    protected array $components = [];

    /**
     * @var array<int,FormField>
     */
    protected array $fields = [];

    /**
     * Set the component schema (child components and/or fields)
     *
     * Supports mixed content:
     * - AbstractField instances (TextInput, Textarea, etc.)
     * - FormComponent instances (Group, Tabs, Div, etc.)
     *
     * @param  array<int,FormComponent|FormField>  $components
     * @return $this
     */
    public function schema(array $components): static
    {
        $this->components = [];
        $this->fields = [];

        foreach ($components as $component) {
            // Handle AbstractField (form fields)
            if ($component instanceof FormField) {
                $this->fields[] = $component;
                continue;
            }

            // Handle FormComponent (layout containers)
            if ($component instanceof FormComponent) {
                $component->setParent($this);
                $this->components[] = $component;
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

    /**
     * Get child components (layout containers only)
     *
     * @return array<int,FormComponent>
     */
    public function getChildComponents(): array
    {
        return $this->components;
    }

    /**
     * Get direct child fields
     *
     * @return array<int,FormField>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function hasChildComponents(): bool
    {
        return !empty($this->components) || !empty($this->fields);
    }
}
