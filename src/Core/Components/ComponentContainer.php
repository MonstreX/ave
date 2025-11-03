<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\Components\Concerns\HasComponents;
use Monstrex\Ave\Core\FormContext;

/**
 * Base class for container components that hold other components
 *
 * Used by Tabs, Panel, Group, Columns, Column
 */
abstract class ComponentContainer extends FormComponent
{
    use HasComponents;

    /**
     * Render all child components
     */
    protected function renderChildComponents(FormContext $context): string
    {
        return collect($this->getChildComponents())
            ->map(fn (FormComponent $component): string => $component->render($context))
            ->implode(PHP_EOL);
    }
}
