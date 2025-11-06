<?php

namespace Monstrex\Ave\Core\Fields\Presets\Media;

/**
 * Single Image preset - single image with basic metadata
 *
 * Includes: single image upload, title and alt text properties
 */
class SingleImagePreset extends MediaPreset
{
    /**
     * Configure for single image upload
     */
    public function apply($field)
    {
        return $field
            ->acceptImages()
            ->maxFileSize(5120)
            ->props('title', 'alt');
    }

    public function description(): string
    {
        return 'Single image upload with title and alt text';
    }
}
