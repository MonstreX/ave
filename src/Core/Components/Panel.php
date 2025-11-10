<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\FormContext;

/**
 * Panel - Alias for Div with Bootstrap panel styling
 *
 * Convenient wrapper for creating styled panels with optional title/header.
 * Uses Bootstrap panel classes and custom Blade template.
 *
 * Features:
 * - Automatic panel-heading with title
 * - panel-body for content
 * - panel-footer support via footer() method
 * - Custom CSS classes via classes() method
 * - All Div functionality (schema, header, footer, attributes)
 *
 * Example:
 *   Panel::make('Contact Information')->schema([
 *       TextInput::make('email'),
 *       TextInput::make('phone'),
 *   ])
 *
 *   Panel::make('Settings')
 *       ->classes('panel-info')
 *       ->schema([...])
 */
class Panel extends Div
{
    public static function make(string $title = ''): static
    {
        $instance = parent::make('panel panel-default');

        if ($title) {
            $instance->header($title);
        }

        return $instance;
    }

    /**
     * Override to use custom panel template
     */
    protected function getViewName(): string
    {
        return 'ave::components.forms.panel';
    }
}
