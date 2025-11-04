<?php

namespace Monstrex\Ave\Core\Components\Concerns;

use InvalidArgumentException;
use Monstrex\Ave\Core\Components\FormComponent;
use Monstrex\Ave\Core\Components\RowComponent;
use Monstrex\Ave\Core\FormRow;

/**
 * Trait for components that contain child components
 *
 * Used by container components like Tabs, Panel, Group, Columns
 */
trait HasComponents
{
    /**
     * @var array<int,FormComponent>
     */
    protected array $components = [];

    /**
     * Set the component schema (child components)
     *
     * @param  array<int,FormComponent>  $components
     * @return $this
     */
    public function schema(array $components): static
    {
        $this->components = [];

        foreach ($components as $component) {
            if ($component instanceof FormRow) {
                $component = RowComponent::fromFormRow($component);
            }

            if (!$component instanceof FormComponent) {
                throw new InvalidArgumentException('Form components must extend FormComponent.');
            }

            $component->setParent($this);
            $this->components[] = $component;
        }

        return $this;
    }

    /**
     * Get child components
     *
     * @return array<int,FormComponent>
     */
    public function getChildComponents(): array
    {
        return $this->components;
    }

    public function hasChildComponents(): bool
    {
        return !empty($this->components);
    }
}
