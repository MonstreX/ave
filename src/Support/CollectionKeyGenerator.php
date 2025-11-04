<?php

namespace Monstrex\Ave\Support;

use Illuminate\Support\Str;

class CollectionKeyGenerator
{
    /**
     * Build a stable collection key for media fields based on the declared collection name
     * and the HTML key of the field (including bracket notation for nested items).
     */
    public static function forMedia(string $declaredCollection, string $htmlKey, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        $segments = self::extractSegments($htmlKey);

        if (count($segments) > 1) {
            array_pop($segments); // drop field segment
        } else {
            $segments = [];
        }

        if (empty($segments)) {
            return $declaredCollection;
        }

        $path = self::implodeSegments($segments);
        $base = Str::lower($declaredCollection);

        $formatted = "{$base}.{$path}";

        if (Str::length($formatted) > 120) {
            $hash = substr(md5($formatted), 0, 12);
            $formatted = "{$base}.{$hash}";
        }

        return trim($formatted, '.');
    }

    /**
     * Build a meta-key used for hidden form inputs.
     */
    public static function metaKeyForField(string $htmlKey): string
    {
        $segments = self::extractSegments($htmlKey);

        if (empty($segments)) {
            return Str::lower($htmlKey);
        }

        return self::implodeSegments($segments);
    }

    /**
     * @return array<int,string>
     */
    protected static function extractSegments(string $htmlKey): array
    {
        $segments = preg_split('/[\[\]]+/', $htmlKey, -1, PREG_SPLIT_NO_EMPTY);

        return array_map(
            static fn (string $segment): string => Str::of($segment)->trim()->toString(),
            $segments ?: []
        );
    }

    protected static function implodeSegments(array $segments): string
    {
        return Str::of(
            implode(
                '.',
                array_map(
                    static fn (string $segment): string => Str::lower($segment),
                    $segments
                )
            )
        )
            ->replaceMatches('/\.+/', '.')
            ->trim('.')
            ->toString();
    }
}
