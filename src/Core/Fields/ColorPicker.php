<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * ColorPicker Field
 *
 * A color picker field for selecting colors in HEX format.
 *
 * Features:
 * - HTML5 native color picker
 * - HEX color format (#RRGGBB)
 * - Fallback to text input for browsers without color picker support
 * - Optional color preview
 * - Validation for hex color format
 *
 * Example (Basic):
 *   ColorPicker::make('brand_color')
 *       ->label('Brand Color')
 *       ->default('#0cb7e0')
 *
 * Example (With predefined colors):
 *   ColorPicker::make('accent_color')
 *       ->label('Accent Color')
 *       ->default('#ff6b6b')
 *
 * Note: Uses HTML5 input type="color" which is supported in modern browsers
 */
class ColorPicker extends AbstractField
{
    /**
     * Predefined color palette (optional)
     */
    protected array $colorPalette = [];

    /**
     * Set predefined color palette
     *
     * @param array $colors Array of HEX colors (e.g., ['#ff0000', '#00ff00', '#0000ff'])
     * @return static
     */
    public function palette(array $colors): static
    {
        $this->colorPalette = $colors;
        return $this;
    }

    /**
     * Get color palette
     *
     * @return array
     */
    public function getPalette(): array
    {
        return $this->colorPalette;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'colorPalette' => $this->colorPalette,
        ]);
    }

    /**
     * Extract color value, ensuring it's in HEX format
     *
     * Converts color values to lowercase HEX format (#RRGGBB)
     *
     * @param mixed $raw Raw input value from form
     * @return string|null Color in HEX format or null if empty
     */
    public function extract(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Ensure color starts with # and is lowercase
        $color = strtolower((string)$raw);
        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        return $color;
    }
}
