<?php

namespace Monstrex\Ave\Support;

/**
 * MetaKeyGenerator converts state paths to meta keys for use in HTML and JavaScript.
 *
 * The metaKey is used in:
 * - HTML data-meta-key attributes
 * - Hidden input names for media data
 * - JavaScript event tracking
 *
 * Converts dot notation state paths to underscore notation for use in attributes.
 *
 * Examples:
 * - 'gallery.0.image' → 'gallery_0_image'
 * - 'features.1.icon' → 'features_1_icon'
 * - 'avatar' → 'avatar'
 */
class MetaKeyGenerator
{
    /**
     * Generate a meta key from a state path.
     *
     * Converts dot notation to underscore notation for use in HTML attributes and JS.
     *
     * @param string $statePath The field's state path (e.g., 'gallery.0.image')
     * @return string The meta key (e.g., 'gallery_0_image')
     */
    public static function fromStatePath(string $statePath): string
    {
        // Convert dots to underscores
        $metaKey = str_replace('.', '_', $statePath);

        // Ensure it's lowercase (consistency with JS computeMetaKey)
        return strtolower($metaKey);
    }

}
