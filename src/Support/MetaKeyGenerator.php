<?php

namespace Monstrex\Ave\Support;

/**
 * MetaKeyGenerator creates unique identifiers for media fields based on state paths.
 *
 * This generator ensures JavaScript code can correctly identify and track media fields,
 * especially in nested contexts like Fieldset items.
 *
 * The metaKey is used in:
 * - HTML data-meta-key attributes
 * - Hidden input names for media collections
 * - JavaScript mediaContainers Map keys
 * - Media deletion tracking
 *
 * Example:
 * - State path: 'gallery.0.image' → Meta key: 'gallery_0_image'
 * - State path: 'features.1.icon' → Meta key: 'features_1_icon'
 * - State path: 'avatar' → Meta key: 'avatar'
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

    /**
     * Generate meta key from field name and parent path (legacy support).
     *
     * @param string $fieldName The field's base name (e.g., 'image')
     * @param string|null $parentPath The parent container path (e.g., 'gallery.0')
     * @return string The meta key
     */
    public static function fromParts(?string $parentPath, string $fieldName): string
    {
        if (empty($parentPath)) {
            return strtolower($fieldName);
        }

        $fullPath = "{$parentPath}.{$fieldName}";

        return self::fromStatePath($fullPath);
    }

    /**
     * Check if a meta key indicates a template field.
     *
     * @param string $metaKey The meta key to check
     * @return bool True if this is a template meta key
     */
    public static function isTemplateMetaKey(string $metaKey): bool
    {
        return str_contains($metaKey, '__template__');
    }

    /**
     * Extract parent path from meta key.
     *
     * Example: 'features_0_icon' → 'features_0'
     *
     * @param string $metaKey The meta key
     * @return string|null The parent meta key, or null if root level
     */
    public static function getParentMetaKey(string $metaKey): ?string
    {
        $parts = explode('_', $metaKey);

        if (count($parts) <= 1) {
            return null;
        }

        array_pop($parts);

        return implode('_', $parts);
    }
}
