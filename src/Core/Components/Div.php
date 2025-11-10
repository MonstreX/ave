<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\FormContext;

/**
 * Div - Universal container component for grouping fields and components
 *
 * A flexible container that can:
 * - Group form fields together
 * - Contain nested Div components
 * - Mix fields and components in any order
 * - Support custom CSS classes
 * - Optional header and footer sections
 *
 * Examples:
 * ```php
 * // Simple grid row
 * Div::make('row')->schema([
 *     Div::make('col-6')->schema([TextInput::make('name')]),
 *     Div::make('col-6')->schema([TextInput::make('email')]),
 * ])
 *
 * // Panel with header
 * Div::make('panel')->header('Main Section')->schema([
 *     TextInput::make('title'),
 *     Textarea::make('description'),
 * ])
 *
 * // Mixed content (fields + nested divs)
 * Div::make('card')->schema([
 *     TextInput::make('title'),  // Direct field
 *     Div::make('row')->schema([ // Nested container
 *         Div::make('col-6')->schema([TextInput::make('name')]),
 *     ]),
 * ])
 * ```
 */
class Div extends ComponentContainer
{
    protected string $classes = '';

    protected ?string $header = null;

    protected ?string $footer = null;

    protected array $attributes = [];

    public static function make(string $classes = ''): static
    {
        $instance = new static();
        $instance->classes = $classes;

        return $instance;
    }

    /**
     * Set CSS classes for the div
     */
    public function classes(string $classes): static
    {
        $this->classes = $classes;

        return $this;
    }

    /**
     * Get CSS classes
     */
    public function getClasses(): string
    {
        return $this->classes;
    }

    /**
     * Set header content (rendered before children)
     */
    public function header(?string $header): static
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Get header content
     */
    public function getHeader(): ?string
    {
        return $this->header;
    }

    /**
     * Set footer content (rendered after children)
     */
    public function footer(?string $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Get footer content
     */
    public function getFooter(): ?string
    {
        return $this->footer;
    }

    /**
     * Set HTML attributes (id, data-*, aria-*, etc.)
     *
     * @param array<string,string|int|bool> $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get HTML attributes
     *
     * @return array<string,string|int|bool>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function getDefaultViewTemplate(): string
    {
        return 'ave::components.forms.div';
    }

    public function render(FormContext $context): string
    {
        return view($this->getViewTemplate(), [
            'component' => $this,
            'classes' => $this->classes,
            'header' => $this->header,
            'footer' => $this->footer,
            'attributes' => $this->buildAttributesString(),
            'fieldsContent' => $this->renderChildFields($context),
            'componentsContent' => $this->renderChildComponents($context),
        ])->render();
    }

    /**
     * Convert HTML attributes array to string
     *
     * Example: ['id' => 'main', 'data-test' => 'value']
     *          → 'id="main" data-test="value"'
     */
    private function buildAttributesString(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        return collect($this->attributes)
            ->map(fn ($value, $key) => sprintf('%s="%s"', $key, htmlspecialchars((string)$value, ENT_QUOTES)))
            ->implode(' ');
    }
}
