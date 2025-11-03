<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\FormContext;

/**
 * Panel component - styled container with title and description
 *
 * Example:
 * ```php
 * Panel::make('SEO Settings')
 *     ->description('Configure search engine optimization')
 *     ->collapsible()
 *     ->collapsed()
 *     ->schema([...])
 * ```
 */
class Panel extends ComponentContainer
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected bool $collapsible = false;

    protected bool $collapsed = false;

    protected function getDefaultViewTemplate(): string
    {
        return 'ave::components.forms.panel';
    }

    public static function make(?string $title = null, array $components = []): static
    {
        $instance = new static;

        if ($title !== null) {
            $instance->title($title);
        }

        if ($components) {
            $instance->schema($components);
        }

        return $instance;
    }

    /**
     * Set the panel title
     */
    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the panel description
     */
    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Make the panel collapsible
     */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * Set the panel to collapsed by default
     */
    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function render(FormContext $context): string
    {
        return view($this->getViewTemplate(), [
            'component' => $this,
            'title' => $this->title,
            'description' => $this->description,
            'collapsible' => $this->collapsible,
            'collapsed' => $this->collapsed,
            'content' => $this->renderChildComponents($context),
        ])->render();
    }
}
