<?php

namespace Monstrex\Ave\Core\Fields\Presets\CodeEditor;

/**
 * HTML preset - HTML/template code editing with syntax highlighting
 *
 * Ideal for HTML snippets, templates, and markup editing
 */
class HtmlPreset extends CodeEditorPreset
{
    /**
     * HTML language
     */
    public function language(): string
    {
        return 'html';
    }

    /**
     * Default height for HTML editor
     */
    public function height(): int
    {
        return 400;
    }

    /**
     * Use monokai theme for better visibility
     */
    public function theme(): string
    {
        return 'monokai';
    }

    /**
     * Auto-height for flexible content
     */
    public function autoHeight(): bool
    {
        return true;
    }

    public function description(): string
    {
        return 'HTML code editor with syntax highlighting and auto-height';
    }
}
