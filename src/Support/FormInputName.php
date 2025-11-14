<?php

namespace Monstrex\Ave\Support;

class FormInputName
{
    /**
     * Convert a state path (dot notation) into an HTML id attribute.
     */
    public static function idFromStatePath(string $statePath): string
    {
        $segments = self::extractSegments($statePath);
        if (empty($segments)) {
            return self::sanitizeId($statePath);
        }

        return self::sanitizeId(implode('-', $segments));
    }

    /**
     * Convert a state path into HTML bracket notation (e.g., sections[0][title]).
     */
    public static function nameFromStatePath(string $statePath): string
    {
        $segments = self::extractSegments($statePath);
        if (empty($segments)) {
            return $statePath;
        }

        $first = array_shift($segments);

        return $first . implode('', array_map(
            static fn (string $segment) => "[{$segment}]",
            $segments
        ));
    }

    /**
     * Normalize any key (dot or bracket notation) into dot notation.
     */
    public static function toDotNotation(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $normalized = preg_replace('/\[(.*?)\]/', '.$1', $value);
        $normalized = preg_replace('/\.+/', '.', (string) $normalized);

        return trim((string) $normalized, '.');
    }

    /**
     * Normalize and split the state path into clean segments.
     *
     * @return array<int,string>
     */
    protected static function extractSegments(string $statePath): array
    {
        $normalized = self::toDotNotation($statePath);

        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            explode('.', $normalized),
            static fn ($segment) => $segment !== '' && $segment !== null
        ));
    }

    protected static function sanitizeId(string $id): string
    {
        $id = preg_replace('/[^A-Za-z0-9_-]+/', '-', $id);
        $id = trim((string) $id, '-');

        return $id === '' ? 'field-' . uniqid() : $id;
    }
}
