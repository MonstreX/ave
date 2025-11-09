<?php

namespace Monstrex\Ave\Services;

use Illuminate\Support\Str;

/**
 * SlugService - generates URL-friendly slugs from strings.
 *
 * Provides a centralized service for slug generation with support for
 * multiple languages and separators. Uses Laravel's Str::slug() for
 * transliteration and normalization.
 *
 * Example:
 *   SlugService::make('Привет мир', '-', 'ru')
 *   // Returns: 'privet-mir'
 */
class SlugService
{
    /**
     * Generate a URL-friendly slug from a string.
     *
     * @param string $string The input string to slugify
     * @param string $separator The character to use as separator (default: '-')
     * @param string|null $locale Language locale for transliteration (e.g., 'ru', 'uk')
     * @return string The generated slug (empty string if input is empty)
     */
    public static function make(string $string, string $separator = '-', ?string $locale = null): string
    {
        if (empty($string)) {
            return '';
        }

        return Str::slug($string, $separator, $locale);
    }
}
