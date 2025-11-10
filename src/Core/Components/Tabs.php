<?php

namespace Monstrex\Ave\Core\Components;

use InvalidArgumentException;
use Monstrex\Ave\Core\FormContext;

/**
 * Tabs - Tabbed interface container
 *
 * Container for organizing content into multiple tabs.
 * Can only contain Tab components.
 *
 * Features:
 * - Multiple tab panes with individual content
 * - Automatic first-tab-active behavior
 * - Tab navigation with data-driven attributes
 * - Flexible schema with Tab objects
 *
 * Architecture:
 * - Tabs is the container (FormComponent)
 * - Each Tab is a ComponentContainer with fields/components
 * - Navigation and content are rendered from same Tab definitions
 *
 * Example:
 *   Tabs::make()->schema([
 *       Tab::make('Tab 1')->schema([
 *           TextInput::make('field1'),
 *       ]),
 *       Tab::make('Tab 2')->schema([
 *           TextInput::make('field2'),
 *       ]),
 *   ])
 */
class Tabs extends FormComponent
{
    /**
     * @var array<int,Tab>
     */
    protected array $tabs = [];

    protected string $id;

    protected ?string $activeTabId = null;

    public function __construct()
    {
        $this->id = 'tabs-' . uniqid();
    }

    public static function make(array $tabs = []): static
    {
        $instance = new static();
        if (!empty($tabs)) {
            $instance->schema($tabs);
        }

        return $instance;
    }

    /**
     * Set active tab by ID
     */
    public function active(?string $tabId): static
    {
        $this->activeTabId = $tabId;

        return $this;
    }

    /**
     * Get active tab ID
     */
    public function getActiveTab(): ?string
    {
        return $this->activeTabId;
    }

    /**
     * Get DOM ID for tabs container
     */
    public function getDomId(): string
    {
        return $this->id;
    }

    /**
     * Set tabs schema
     *
     * @param array<int,Tab> $tabs
     */
    public function schema(array $tabs): static
    {
        $this->tabs = [];

        foreach ($tabs as $index => $tab) {
            if (!$tab instanceof Tab) {
                throw new InvalidArgumentException(
                    'Tabs container expects Tab components'
                );
            }

            // Set first tab as active by default if not explicitly set
            if ($index === 0 && !$tab->isActive()) {
                $tab->active(true);
            }

            $this->tabs[] = $tab;
        }

        return $this;
    }

    /**
     * Get all tabs
     *
     * @return array<int,Tab>
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * Get active Tab object (helper method - not in interface)
     */
    public function getActiveTabObject(): ?Tab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->isActive()) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * Flatten all fields from all tabs
     */
    public function flattenFields(): array
    {
        $fields = [];

        foreach ($this->tabs as $tab) {
            $fields = array_merge($fields, $tab->flattenFields());
        }

        return $fields;
    }

    /**
     * Render tabs interface
     */
    public function render(FormContext $context): string
    {
        return view('ave::components.forms.tabs', [
            'tabs' => $this->tabs,
            'context' => $context,
            'domId' => $this->id,
        ])->render();
    }
}
