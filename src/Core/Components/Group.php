<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\Forms\FormContext;

/**
 * Group component - semantic fieldset for grouping related fields
 *
 * Example:
 * ```php
 * Group::make('Security Settings')
 *     ->description('Configure security options')
 *     ->schema([...])
 * ```
 */
class Group extends ComponentContainer
{
    protected ?string $label = null;

    protected ?string $description = null;

    protected function getDefaultViewTemplate(): string
    {
        return 'ave::components.forms.group';
    }

    public static function make(?string $label = null, array $components = []): static
    {
        $instance = new static;

        if ($label !== null) {
            $instance->label($label);
        }

        if ($components) {
            $instance->schema($components);
        }

        return $instance;
    }

    /**
     * Set the group label
     */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the group description
     */
    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function render(FormContext $context): string
    {
        return view($this->getViewTemplate(), [
            'component' => $this,
            'label' => $this->label,
            'description' => $this->description,
            'content' => $this->renderChildComponents($context),
        ])->render();
    }
}
