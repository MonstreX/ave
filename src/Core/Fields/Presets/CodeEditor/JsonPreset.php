<?php

namespace Monstrex\Ave\Core\Fields\Presets\CodeEditor;

/**
 * JSON preset - JSON code editing with syntax highlighting
 *
 * Ideal for configuration, data structures, and API responses
 */
class JsonPreset extends CodeEditorPreset
{
    /**
     * JSON language
     */
    public function language(): string
    {
        return 'json';
    }

    /**
     * Default height for JSON editor
     */
    public function height(): int
    {
        return 300;
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
        return 'JSON code editor with syntax highlighting and auto-height';
    }
}
