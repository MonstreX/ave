<?php

namespace Monstrex\Ave\Core\Components;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;

/**
 * Base class for form layout components
 *
 * Provides hierarchy and rendering for form layout elements:
 * - Tabs, Tab
 * - Panel
 * - Group
 * - Columns, Column
 */
abstract class FormComponent
{
    /**
     * Manual view override
     */
    protected ?string $view = null;

    protected ?FormComponent $parent = null;

    protected ?Form $form = null;

    /**
     * Assign the current form instance to the component (and children)
     */
    public function assignForm(Form $form): void
    {
        $this->form = $form;

        foreach ($this->getChildComponents() as $component) {
            $component->assignForm($form);
        }
    }

    public function form(): ?Form
    {
        return $this->form;
    }

    public function setParent(?FormComponent $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?FormComponent
    {
        return $this->parent;
    }

    /**
     * Get child components
     *
     * @return array<int,FormComponent>
     */
    public function getChildComponents(): array
    {
        return [];
    }

    public function hasChildComponents(): bool
    {
        return !empty($this->getChildComponents());
    }

    /**
     * Prepare component for display (before rendering)
     */
    public function prepareForDisplay(FormContext $context): void
    {
        foreach ($this->getChildComponents() as $component) {
            $component->prepareForDisplay($context);
        }
    }

    /**
     * Prepare component for validation
     */
    public function prepareForValidation(Request $request, FormContext $context): void
    {
        foreach ($this->getChildComponents() as $component) {
            $component->prepareForValidation($request, $context);
        }
    }

    /**
     * Get all fields from component and children (recursively)
     *
     * Flattens all fields from:
     * - Direct child fields (if component has $fields property from HasComponents)
     * - Child components (FormComponent instances)
     *
     * @return array
     */
    public function flattenFields(): array
    {
        $fields = [];

        // Get direct fields if this component supports them (via HasComponents trait)
        if (method_exists($this, 'getFields')) {
            $fields = array_merge($fields, $this->getFields());
        }

        // Recursively get fields from child components
        foreach ($this->getChildComponents() as $component) {
            $fields = array_merge($fields, $component->flattenFields());
        }

        return $fields;
    }

    /**
     * Get the default view template for this component
     */
    protected function getDefaultViewTemplate(): string
    {
        return '';
    }

    /**
     * Get the view template
     */
    public function getViewTemplate(): string
    {
        if ($this->view) {
            return $this->view;
        }

        return $this->getDefaultViewTemplate();
    }

    /**
     * Override view template
     */
    public function view(string $view): static
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Render the component
     */
    abstract public function render(FormContext $context): string;
}
