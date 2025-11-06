<?php

namespace Monstrex\Ave\Core\Fields\Presets\Media;

/**
 * Gallery preset - multiple images with metadata and 8-column grid
 *
 * Includes: multiple images (up to 20), rich metadata (title, alt, caption, position), grid display
 */
class GalleryPreset extends MediaPreset
{
    /**
     * Configure for gallery with multiple images
     */
    public function apply($field)
    {
        return $field
            ->acceptImages()
            ->multiple(true, maxFiles: 20)
            ->columns(8)
            ->maxFileSize(5120)
            ->props('title', 'alt', 'caption', 'position');
    }

    public function description(): string
    {
        return 'Image gallery with multiple uploads, metadata and 8-column grid display';
    }
}
