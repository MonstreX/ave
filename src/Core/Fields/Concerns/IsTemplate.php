<?php

namespace Monstrex\Ave\Core\Fields\Concerns;

/**
 * IsTemplate trait marks fields as template instances.
 *
 * When a Fieldset needs to render an "Add Item" template for JavaScript,
 * it uses a placeholder item ID like '__ITEM__'. This trait prevents
 * such template fields from polluting actual collection names and state paths.
 *
 * Example:
 * - Real field: Fieldset::make('items[0]') -> collection 'default.items.0'
 * - Template:   Fieldset::make('items[__ITEM__]') -> path 'items.__TEMPLATE__' (never stored)
 */
trait IsTemplate
{
    protected bool $isTemplate = false;

    /**
     * Mark this field as a template instance.
     *
     * When true, the field's state path will use '__TEMPLATE__' marker
     * instead of real item identifiers, preventing database pollution.
     */
    public function markAsTemplate(): static
    {
        $clone = clone $this;
        $clone->isTemplate = true;

        return $clone;
    }

    /**
     * Check if this field is a template instance.
     */
    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    /**
     * Get state path safe for template rendering.
     *
     * When a field is marked as template and has a container, this method
     * replaces any placeholder item IDs with a clean '__TEMPLATE__' marker.
     *
     * This prevents template fields from accidentally creating collection names
     * that would be used for real data storage.
     *
     * Example:
     * - Normal: 'items.0.image' -> collection 'default.items.0'
     * - Template: 'items.__TEMPLATE__.image' -> never stored
     */
    public function getTemplateSafeStatePath(): string
    {
        if ($this->isTemplate && $this->container) {
            try {
                $parentPath = $this->container->getChildStatePath();
                // Return parent path with __TEMPLATE__ marker instead of item ID
                return "{$parentPath}.__TEMPLATE__";
            } catch (\Exception $e) {
                // Fall back to normal path
            }
        }

        return $this->getStatePath();
    }
}
