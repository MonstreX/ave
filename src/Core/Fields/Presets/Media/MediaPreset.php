<?php

namespace Monstrex\Ave\Core\Fields\Presets\Media;

/**
 * Base class for Media field presets
 *
 * Presets provide pre-configured settings for common media field use cases.
 */
abstract class MediaPreset
{
    /**
     * Apply preset configuration to a media field
     *
     * This method should be overridden by subclasses to configure the media field
     * with specific settings.
     *
     * @param \Monstrex\Ave\Core\Fields\Media $field
     * @return \Monstrex\Ave\Core\Fields\Media
     */
    abstract public function apply($field);

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
