<?php

namespace Monstrex\Ave\Core\Fields\Presets\RichEditor;

/**
 * Base class for RichEditor presets
 *
 * Presets provide pre-configured feature sets and options for the RichEditor field.
 */
abstract class RichEditorPreset
{
    /**
     * Get the feature tokens for this preset
     *
     * @return array|string|null Array of tokens, comma-separated string, or null for all defaults
     */
    abstract public function features(): array|string|null;

    /**
     * Get additional Jodit options for this preset
     *
     * @return array
     */
    public function options(): array
    {
        return [];
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
