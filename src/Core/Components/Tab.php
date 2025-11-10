<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\FormContext;

/**
 * Tab - Individual tab within a Tabs container
 *
 * Represents a single tab pane within a tabbed interface.
 * Can only be used within a Tabs container.
 *
 * Features:
 * - Custom tab ID (auto-generated or manual)
 * - Title/label for tab navigation
 * - Active state (first tab is active by default)
 * - Can contain fields and nested components
 *
 * Example:
 *   Tab::make('Personal Info')->schema([
 *       TextInput::make('first_name'),
 *       TextInput::make('last_name'),
 *   ])
 *
 * Usage (always within Tabs):
 *   Tabs::make()->schema([
 *       Tab::make('Tab 1')->schema([...]),
 *       Tab::make('Tab 2')->schema([...]),
 *   ])
 */
class Tab extends ComponentContainer
{
    protected string $id;

    protected string $title;

    protected bool $active = false;

    protected ?string $icon = null;

    protected ?string $badge = null;

    public function __construct(string $title)
    {
        $this->title = $title;
        // Auto-generate unique ID from title (kebab-case) with hash suffix
        $this->id = 'tab-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title))) . '-' . substr(md5(uniqid()), 0, 8);
    }

    public static function make(string $title): static
    {
        return new static($title);
    }

    /**
     * Set custom tab ID
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get tab ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get tab title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get tab label (alias for getTitle)
     */
    public function getLabel(): string
    {
        return $this->title;
    }

    /**
     * Set tab icon (CSS class) - accepts null to clear
     */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get tab icon
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Set tab badge text - accepts null to clear
     */
    public function badge(?string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Get tab badge
     */
    public function getBadge(): ?string
    {
        return $this->badge;
    }

    /**
     * Set active state
     */
    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Check if tab is active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Tab uses standard component container rendering
     */
    public function render(FormContext $context): string
    {
        return view('ave::components.forms.tab-panel', [
            'content' => $this->renderAllChildren($context),
        ])->render();
    }
}
