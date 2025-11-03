<?php

namespace Monstrex\Ave\Core\Components;

use InvalidArgumentException;
use Monstrex\Ave\Core\FormContext;

/**
 * Tabs component - renders Bootstrap tabs with multiple Tab children
 *
 * Example:
 * ```php
 * Tabs::make([
 *     Tab::make('General')->schema([...]),
 *     Tab::make('Settings')->schema([...]),
 * ])->active('tab-general')
 * ```
 */
class Tabs extends ComponentContainer
{
    protected ?string $activeTab = null;

    protected function getDefaultViewTemplate(): string
    {
        return 'ave::components.forms.tabs';
    }

    public static function make(array $tabs = []): static
    {
        $instance = new static;

        if ($tabs) {
            $instance->schema($tabs);
        }

        return $instance;
    }

    public function schema(array $components): static
    {
        foreach ($components as $component) {
            if (!$component instanceof Tab) {
                throw new InvalidArgumentException('Tabs container expects Tab components.');
            }
        }

        return parent::schema($components);
    }

    /**
     * Set the active tab by ID
     */
    public function active(string $tabId): static
    {
        $this->activeTab = $tabId;

        return $this;
    }

    public function getActiveTab(): ?string
    {
        return $this->activeTab;
    }

    /**
     * Generate unique DOM ID for this tabs container
     */
    public function getDomId(): string
    {
        return 'tabs-' . substr(md5(spl_object_hash($this)), 0, 8);
    }

    public function render(FormContext $context): string
    {
        $tabs = $this->getChildComponents();

        $active = $this->activeTab;
        if (!$active && isset($tabs[0])) {
            $active = $tabs[0]->getId();
        }

        return view($this->getViewTemplate(), [
            'component' => $this,
            'tabs' => $tabs,
            'context' => $context,
            'activeTab' => $active,
            'domId' => $this->getDomId(),
        ])->render();
    }
}
