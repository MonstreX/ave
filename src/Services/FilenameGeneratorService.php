<?php

namespace Monstrex\Ave\Services;

use Illuminate\Http\UploadedFile;

/**
 * FilenameGeneratorService - Unified filename generation for all file uploads
 *
 * Supports three strategies:
 * - original: Keep the original filename as-is
 * - transliterate: Transliterate filename to slug format using SlugService
 * - unique: Generate completely random unique filename using Laravel's hashName()
 */
class FilenameGeneratorService
{
    /**
     * Strategy constant: Keep original filename
     */
    const STRATEGY_ORIGINAL = 'original';

    /**
     * Strategy constant: Transliterate to slug
     */
    const STRATEGY_TRANSLITERATE = 'transliterate';

    /**
     * Strategy constant: Generate unique random name
     */
    const STRATEGY_UNIQUE = 'unique';

    /**
     * Uniqueness handling: Add suffix -1, -2, -3, etc.
     */
    const UNIQUENESS_SUFFIX = 'suffix';

    /**
     * Uniqueness handling: Replace existing file
     */
    const UNIQUENESS_REPLACE = 'replace';

    /**
     * Generate filename based on strategy
     *
     * @param string|UploadedFile $filename Original filename or UploadedFile instance
     * @param array $options Configuration options:
     *   - strategy: 'original'|'transliterate'|'unique' (default: 'original')
     *   - separator: Separator for transliterate (default: '-')
     *   - locale: Locale for transliterate (default: 'ru')
     *   - uniqueness: 'suffix'|'replace' (default: 'suffix')
     *   - existsCallback: Callable to check if file exists at path
     *   - maxLength: Max filename length before extension (default: null)
     * @return string Generated filename with extension
     */
    public static function generate($filename, array $options = []): string
    {
        // Extract filename if UploadedFile instance
        if ($filename instanceof UploadedFile) {
            $originalName = $filename->getClientOriginalName();
        } else {
            $originalName = (string) $filename;
        }

        // Parse filename
        $pathInfo = pathinfo($originalName);
        $basename = $pathInfo['filename'] ?? '';
        $extension = $pathInfo['extension'] ?? '';

        // Set defaults
        $strategy = $options['strategy'] ?? self::STRATEGY_ORIGINAL;
        $separator = $options['separator'] ?? '-';
        $locale = $options['locale'] ?? 'ru';
        $uniqueness = $options['uniqueness'] ?? self::UNIQUENESS_SUFFIX;
        $existsCallback = $options['existsCallback'] ?? null;
        $maxLength = $options['maxLength'] ?? null;

        // Generate base filename based on strategy
        $generatedName = match ($strategy) {
            self::STRATEGY_TRANSLITERATE => static::transliterateFilename($basename, $separator, $locale),
            self::STRATEGY_UNIQUE => static::generateUniqueFilename($originalName),
            self::STRATEGY_ORIGINAL => static::sanitizeFilename($basename),
            default => static::sanitizeFilename($basename),
        };

        // Apply max length if specified
        if ($maxLength && strlen($generatedName) > $maxLength) {
            $generatedName = substr($generatedName, 0, $maxLength);
        }

        // Build full filename with extension
        $fullFilename = $extension
            ? $generatedName . '.' . $extension
            : $generatedName;

        // Handle uniqueness if callback provided
        if ($existsCallback && $uniqueness === self::UNIQUENESS_SUFFIX) {
            $fullFilename = static::ensureUnique($fullFilename, $existsCallback);
        }

        return $fullFilename;
    }

    /**
     * Transliterate filename to slug format
     *
     * @param string $basename Filename without extension
     * @param string $separator Separator character
     * @param string $locale Language locale for transliteration
     * @return string Transliterated filename
     */
    protected static function transliterateFilename(string $basename, string $separator = '-', string $locale = 'ru'): string
    {
        // Use SlugService for transliteration (consistent with slug field)
        $slugged = SlugService::make($basename, $separator, $locale);

        // SlugService returns lowercase slug, which is what we want
        return $slugged ?: 'file';
    }

    /**
     * Generate completely unique random filename
     *
     * @param string $originalFilename Original filename to extract extension
     * @return string Random unique filename (Laravel hashName style)
     */
    protected static function generateUniqueFilename(string $originalFilename): string
    {
        // Generate random string using Laravel's approach
        // This produces names like: a7f3b9c1e5d4f8a2b6c9e1f3a5d7b8e1
        $randomString = bin2hex(random_bytes(16));

        return $randomString;
    }

    /**
     * Sanitize filename - remove special characters but keep readable format
     *
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    protected static function sanitizeFilename(string $filename): string
    {
        // Remove leading/trailing spaces
        $filename = trim($filename);

        // If empty after trim, return default
        if (empty($filename)) {
            return 'file';
        }

        // For original strategy, we keep the filename mostly as-is
        // Only remove problematic characters for filesystem
        $filename = preg_replace('/[\x00-\x1f\x7f-\x9f\/\\?*:|"<>]/', '', $filename);

        return $filename ?: 'file';
    }

    /**
     * Ensure filename is unique by adding suffix -1, -2, -3, etc.
     *
     * @param string $filename Filename to make unique
     * @param callable $existsCallback Callback to check if file exists: fn(string $path) => bool
     * @return string Unique filename
     */
    protected static function ensureUnique(string $filename, callable $existsCallback): string
    {
        // Check if file exists
        if (!$existsCallback($filename)) {
            return $filename;
        }

        // File exists, add suffix
        $pathInfo = pathinfo($filename);
        $basename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        $counter = 1;
        $maxAttempts = 1000;

        while ($counter < $maxAttempts) {
            $newFilename = $basename . '-' . $counter;
            if ($extension) {
                $newFilename .= '.' . $extension;
            }

            if (!$existsCallback($newFilename)) {
                return $newFilename;
            }

            $counter++;
        }

        // Fallback: use timestamp
        return $basename . '-' . time() . ($extension ? '.' . $extension : '');
    }
}
