<?php

namespace Monstrex\Ave\Core\Fields\Presets\Media;

/**
 * Icons preset - multiple small images for icons/thumbnails
 *
 * Includes: multiple image uploads (up to 10), small file size limit, basic metadata
 */
class IconsPreset extends MediaPreset
{
    /**
     * Configure for multiple small icons
     */
    public function apply($field)
    {
        return $field
            ->acceptImages()
            ->multiple(true, maxFiles: 10)
            ->maxFileSize(2048)
            ->props('title', 'alt');
    }

    public function description(): string
    {
        return 'Multiple icon/thumbnail uploads (max 2MB each, up to 10 files)';
    }
}
