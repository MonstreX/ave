<?php

namespace Monstrex\Ave\Support;

class FormKey
{
    /**
     * Convert an HTML form key (with bracket notation) into a flattened meta key.
     */
    public static function toMeta(string $htmlKey): string
    {
        $metaKey = str_replace(['[', ']'], '_', $htmlKey);
        $metaKey = preg_replace('/_+/', '_', $metaKey);

        return trim($metaKey, '_');
    }
}

