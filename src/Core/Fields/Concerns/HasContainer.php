<?php

namespace Monstrex\Ave\Core\Fields\Concerns;

/**
 * HasContainer trait provides parent container awareness to fields.
 *
 * When a field is placed inside a container (Fieldset or Form),
 * the container is stored via this trait. This enables compositional
 * state path construction and proper field nesting awareness.
 */
trait HasContainer
{
    protected ?object $container = null;

    /**
     * Set the parent container (field or component).
     * Typically called by the parent when creating child instances.
     *
     * @param object|null $container The parent container, or null to unset
     */
    public function container(?object $container): static
    {
        $clone = clone $this;
        $clone->container = $container;

        return $clone;
    }

    /**
     * Get the immediate parent container.
     */
    public function getContainer(): ?object
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
    public function getRootContainer(): ?object
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
