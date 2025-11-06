<?php

namespace Monstrex\Ave\Core\Fields\Presets\CodeEditor;

/**
 * Base class for CodeEditor field presets
 *
 * Presets provide pre-configured settings for common code editing use cases.
 */
abstract class CodeEditorPreset
{
    /**
     * Get the programming language for this preset
     *
     * @return string
     */
    abstract public function language(): string;

    /**
     * Get the editor height in pixels
     *
     * @return int
     */
    public function height(): int
    {
        return 300;
    }

    /**
     * Get the editor theme (light, dark, monokai, etc.)
     *
     * @return string
     */
    public function theme(): string
    {
        return 'light';
    }

    /**
     * Whether to enable auto-height based on content
     *
     * @return bool
     */
    public function autoHeight(): bool
    {
        return true;
    }

    /**
     * Get the display name of this preset
     *
     * @return string
     */
    public function name(): string
    {
        $classBaseName = class_basename(static::class);
        return str_replace('Preset', '', $classBaseName);
    }

    /**
     * Get the description of this preset
     *
     * @return string
     */
    public function description(): string
    {
        return '';
    }
}
