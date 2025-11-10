<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\Components\Concerns\HasComponents;
use Monstrex\Ave\Core\FormContext;

/**
 * Base class for container components that hold other components and/or fields
 *
 * Used by Tabs, Panel, Group, Div, etc.
 *
 * Supports mixed schema: both FormComponent (containers) and FormField (fields)
 */
abstract class ComponentContainer extends FormComponent
{
    use HasComponents;

    /**
     * Render all direct child fields
     *
     * Fields come first before components
     */
    protected function renderChildFields(FormContext $context): string
    {
        return collect($this->getFields())
            ->map(fn ($field): string => $field->render($context))
            ->implode(PHP_EOL);
    }

    /**
     * Render all child components (containers)
     */
    protected function renderChildComponents(FormContext $context): string
    {
        return collect($this->getChildComponents())
            ->map(fn (FormComponent $component): string => $component->render($context))
            ->implode(PHP_EOL);
    }

    /**
     * Render all children (fields + components)
     *
     * Convenience method that renders both fields and components in order
     */
    protected function renderAllChildren(FormContext $context): string
    {
        $fieldsHtml = $this->renderChildFields($context);
        $componentsHtml = $this->renderChildComponents($context);

        return trim($fieldsHtml . PHP_EOL . $componentsHtml, PHP_EOL);
    }
}
