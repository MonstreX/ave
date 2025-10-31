<?php

namespace Monstrex\Ave\Core;

abstract class Page
{
    public static ?string $label = null;
    public static ?string $icon = null;
    public static ?string $slug = null;
    public static ?int $navSort = null;

    /**
     * Render page and return payload for view
     *
     * @param mixed $ctx Context (typically Request)
     * @return array Payload data
     */
    public static function render($ctx): array
    {
        return [
            'title' => static::$label ?? static::$slug ?? 'Page',
        ];
    }

    /**
     * Get page slug
     */
    public static function getSlug(): string
    {
        return static::$slug ?? strtolower(class_basename(static::class));
    }

    /**
     * Get page label
     */
    public static function getLabel(): string
    {
        return static::$label ?? class_basename(static::class);
    }
}
