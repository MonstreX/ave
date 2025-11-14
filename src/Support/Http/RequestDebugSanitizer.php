<?php

namespace Monstrex\Ave\Support\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RequestDebugSanitizer
{
    /**
     * Default sensitive keys removed from debug payloads.
     *
     * @var array<int,string>
     */
    protected array $defaultSensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_token',
        'secret',
    ];

    /**
     * Sanitize request payload for safe logging.
     *
     * @param  Request|array<string,mixed>  $source
     * @param  array<int,string>  $additionalSensitive
     * @return array<string,mixed>
     */
    public function sanitize(Request|array $source, array $additionalSensitive = []): array
    {
        $payload = $source instanceof Request ? $source->all() : $source;

        $sensitiveKeys = array_values(array_unique(array_filter(
            array_map('strval', array_merge($this->defaultSensitiveKeys, $additionalSensitive))
        )));

        foreach ($sensitiveKeys as $key) {
            $this->forgetKeyVariants($payload, $key);
        }

        return $payload;
    }

    /**
     * Remove sensitive keys in different notations (dot/bracket).
     *
     * @param  array<string,mixed>  $payload
     */
    protected function forgetKeyVariants(array &$payload, string $key): void
    {
        if ($key === '') {
            return;
        }

        $variants = array_filter([
            $key,
            $this->toDotNotation($key),
        ]);

        foreach ($variants as $variant) {
            Arr::forget($payload, $variant);

            if (array_key_exists($variant, $payload)) {
                unset($payload[$variant]);
            }
        }

        $this->removeRecursive($payload, $key);
    }

    protected function removeRecursive(array &$payload, string $key): void
    {
        foreach ($payload as $currentKey => &$value) {
            if ($this->keysEqual($currentKey, $key)) {
                unset($payload[$currentKey]);
                continue;
            }

            if (is_array($value)) {
                $this->removeRecursive($value, $key);
            }
        }
    }

    protected function toDotNotation(string $key): string
    {
        $normalized = preg_replace('/\[(.*?)\]/', '.$1', $key);
        $normalized = preg_replace('/\.+/', '.', (string) $normalized);

        return trim((string) $normalized, '.');
    }

    protected function keysEqual(string $left, string $right): bool
    {
        return $this->normalizeKey($left) === $this->normalizeKey($right);
    }

    protected function normalizeKey(string $key): string
    {
        return strtolower($this->toDotNotation($key));
    }
}
