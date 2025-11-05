<?php

namespace Monstrex\Ave\Core\Fields\Concerns;

use Monstrex\Ave\Contracts\FormComponent;

/**
 * HasContainer trait provides parent container awareness to fields.
 *
 * When a field is placed inside a container (Fieldset or Form),
 * the container is stored via this trait. This enables compositional
 * state path construction and proper field nesting awareness.
 */
trait HasContainer
{
    protected ?FormComponent $container = null;

    /**
     * Set the parent container (field or component).
     * Typically called by the parent when creating child instances.
     *
     * @param FormComponent|null $container The parent container, or null to unset
     */
    public function container(?FormComponent $container): static
    {
        $clone = clone $this;
        $clone->container = $container;

        return $clone;
    }

    /**
     * Get the immediate parent container.
     */
    public function getContainer(): ?FormComponent
    {
        return $this->container;
    }

    /**
     * Check if this field is nested inside a container.
     */
    public function isNested(): bool
    {
        return $this->container !== null;
    }

    /**
     * Get the root container by traversing up the tree.
     *
     * Useful when you need to access the topmost parent (Form, Page, etc.)
     * even through multiple levels of nesting.
     */
    public function getRootContainer(): ?FormComponent
    {
        $container = $this->container;

        while ($container && method_exists($container, 'getContainer')) {
            $parent = $container->getContainer();
            if ($parent === null) {
                break;
            }

            $container = $parent;
        }

        return $container;
    }
}
