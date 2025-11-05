<?php

namespace Monstrex\Ave\Support;

use Monstrex\Ave\Core\Fields\Media;

/**
 * StatePathCollectionGenerator creates media collection names from state paths.
 *
 * NEW APPROACH (Phase 2+): Uses hierarchical state paths instead of parsing HTML keys.
 *
 * This replaces the fragile HTML-key parsing of CollectionKeyGenerator with a
 * deterministic, compositional approach. Collection names are derived from
 * actual state paths that are built top-down during field construction.
 *
 * Examples:
 * - Simple:   'avatar' (no nesting)        -> 'default'
 * - Nested:   'profile.avatar'             -> 'default.profile'
 * - In list:  'sections.0.gallery'         -> 'default.sections.0'
 * - Deep:     'sections.0.items.1.images'  -> 'default.sections.0.items.1'
 */
class StatePathCollectionGenerator
{
    /**
     * Generate collection name from a Media field's state path.
     *
     * Algorithm:
     * 1. Get field's state path (e.g., 'sections.0.gallery')
     * 2. Extract parent path (all segments except the last one)
     * 3. Combine collection name with parent path
     *
     * @param Media $field The media field to process
     * @return string The derived collection name
     */
    public static function forMedia(Media $field): string
    {
        $statePath = $field->getStatePath();
        $baseCollection = $field->getCollection();

        // Extract path segments (all except the field name itself)
        $segments = explode('.', $statePath);
        array_pop($segments); // Remove field name, keep parent path

        // If field is at root level, return base collection
        if (empty($segments)) {
            return $baseCollection;
        }

        // Combine base collection with parent path
        // Example: 'default' + ['sections', '0'] = 'default.sections.0'
        $parentPath = implode('.', $segments);

        return "{$baseCollection}.{$parentPath}";
    }

    /**
     * Check if a state path indicates a template field.
     *
     * Template paths contain '__TEMPLATE__' marker and should never be used
     * for actual data storage or collection creation.
     *
     * @param string $statePath The state path to check
     * @return bool True if this is a template path
     */
    public static function isTemplateStatePath(string $statePath): bool
    {
        return str_contains($statePath, '.__TEMPLATE__');
    }

    /**
     * Remove template marker from a state path.
     *
     * If needed, this can convert a template path back to a real path by
     * stripping the '__TEMPLATE__' marker. Use with caution!
     *
     * @param string $statePath The state path potentially containing marker
     * @return string Clean state path without markers
     */
    public static function cleanStatePath(string $statePath): string
    {
        return str_replace('.__TEMPLATE__', '', $statePath);
    }

    /**
     * Get the parent path from a state path.
     *
     * Useful for determining which collection a field belongs to.
     *
     * @param string $statePath The field's state path
     * @return string|null The parent path, or null if root level
     */
    public static function getParentPath(string $statePath): ?string
    {
        $segments = explode('.', $statePath);
        array_pop($segments);

        return empty($segments) ? null : implode('.', $segments);
    }

    /**
     * Get the field name from a state path.
     *
     * @param string $statePath The state path
     * @return string The last segment (field name)
     */
    public static function getFieldName(string $statePath): string
    {
        $segments = explode('.', $statePath);

        return array_pop($segments) ?? '';
    }

    /**
     * Compose a state path from parent path and field name.
     *
     * Inverse of decomposition methods above.
     *
     * @param string|null $parentPath The parent path (or null for root)
     * @param string $fieldName The field name to append
     * @return string The composed state path
     */
    public static function composePath(?string $parentPath, string $fieldName): string
    {
        if (empty($parentPath)) {
            return $fieldName;
        }

        return "{$parentPath}.{$fieldName}";
    }
}
