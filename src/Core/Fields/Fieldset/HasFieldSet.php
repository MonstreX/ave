<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

/**
 * Trait HasFieldSet
 *
 * Provides convenient methods for working with FieldSet fields,
 * similar to HasMedia trait for media files.
 *
 * Automatically resolves Media fields inside FieldSet elements.
 *
 * @package Monstrex\Ave\Core\Fields\Fieldset
 */
trait HasFieldSet
{
    /**
     * Get FieldSet with resolved Media fields
     *
     * Automatically processes all elements and resolves Media collection names
     * (like "elements.0.collection_name") into actual Media arrays.
     *
     * @param string $fieldName Name of the FieldSet field (e.g., 'elements')
     * @return array Array of elements with resolved Media fields
     *
     * @example
     * $block->getFieldSet('elements'); // Returns elements with resolved images
     */
    public function getFieldSet(string $fieldName): array
    {
        $elements = $this->$fieldName ?? [];

        if (!is_array($elements)) {
            return [];
        }

        return collect($elements)->map(function ($element) {
            return $this->resolveFieldSetElement($element);
        })->toArray();
    }

    /**
     * Get single FieldSet element by index
     *
     * @param string $fieldName Name of the FieldSet field
     * @param int $index Element index (0-based)
     * @return array|null Element with resolved Media or null if not found
     *
     * @example
     * $block->getFieldSetElement('elements', 0); // First element
     */
    public function getFieldSetElement(string $fieldName, int $index): ?array
    {
        $elements = $this->getFieldSet($fieldName);
        return $elements[$index] ?? null;
    }

    /**
     * Resolve Media fields inside a single FieldSet element
     *
     * Detects Media collection names (strings starting with "elements.")
     * and replaces them with actual Media arrays.
     *
     * @param array $element Single FieldSet element
     * @return array Element with resolved Media fields
     */
    protected function resolveFieldSetElement(array $element): array
    {
        foreach ($element as $key => $value) {
            // Check if value is a Media collection name (e.g., "elements.0.block_elements" or "features.0.feature_icons")
            // Pattern: {fieldname}.{index}.{collection} where index is numeric
            if (is_string($value) && preg_match('/^[a-z_]+\.\d+\.[a-z_]+$/', $value)) {
                if (method_exists($this, 'getMedia')) {
                    try {
                        $media = $this->getMedia($value);
                        $element[$key] = $this->mediaCollectionToArray($media);
                    } catch (\Exception $e) {
                        // Media collection not found or error - leave as empty array
                        $element[$key] = [];
                    }
                }
            }
        }

        return $element;
    }

    /**
     * Convert Media collection to array suitable for Liquid templates
     *
     * Returns all Media properties including dynamic props as stdClass.
     *
     * @param \Illuminate\Support\Collection $mediaCollection Media collection from getMedia()
     * @return array Array of media data with url, props, etc.
     */
    protected function mediaCollectionToArray($mediaCollection): array
    {
        return $mediaCollection->map(function ($media) {
            return [
                'url' => $media->url(),
                'fullUrl' => $media->fullUrl(),
                'path' => $media->path(),
                'fileName' => $media->fileName(),
                'size' => $media->size(),
                'mime' => $media->mime(),
                'order' => $media->order(),
                'props' => $media->props(), // stdClass with ALL props (alt, title, any custom)
            ];
        })->toArray();
    }
}
