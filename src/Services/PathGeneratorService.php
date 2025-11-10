<?php

namespace Monstrex\Ave\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * PathGeneratorService - Unified path generation for all file uploads
 *
 * Supports three strategies:
 * - flat: Organize by model and record ID: {root}/{model_table}/{record_id}/
 * - dated: Organize by model and date: {root}/{model_table}/{year}/{month}/
 * - callback: Custom path generation via user-provided callable
 */
class PathGeneratorService
{
    /**
     * Strategy constant: Flat structure by model and record ID
     */
    const STRATEGY_FLAT = 'flat';

    /**
     * Strategy constant: Dated structure by model and date
     */
    const STRATEGY_DATED = 'dated';

    /**
     * Generate path based on strategy
     *
     * @param array $options Configuration options:
     *   - root: Root directory (required) e.g., 'media', 'uploads/files'
     *   - strategy: 'flat'|'dated'|'callback' (default: 'dated')
     *   - model: Eloquent Model instance (optional)
     *   - recordId: Override model's ID (optional)
     *   - year: Year (default: current year)
     *   - month: Month (default: current month)
     *   - callback: Callable for custom path generation
     * @return string Generated path without trailing slash
     */
    public static function generate(array $options = []): string
    {
        $root = $options['root'] ?? 'uploads';
        $strategy = $options['strategy'] ?? self::STRATEGY_DATED;
        $model = $options['model'] ?? null;
        $recordId = $options['recordId'] ?? ($model ? $model->getKey() : null);
        $year = $options['year'] ?? date('Y');
        $month = $options['month'] ?? date('m');
        $callback = $options['callback'] ?? null;

        // Handle custom callback
        if ($strategy === 'callback' && $callback && is_callable($callback)) {
            return static::executeCallback($callback, $root, $model, $recordId, $year, $month);
        }

        // Normalize root - remove trailing slashes
        $root = rtrim($root, '/');

        // Build base path
        $pathParts = [$root];

        // Add model table if model exists
        if ($model instanceof Model) {
            $pathParts[] = $model->getTable();
        }

        // Apply strategy
        switch ($strategy) {
            case self::STRATEGY_FLAT:
                // Add record ID
                if ($recordId !== null) {
                    $pathParts[] = (string) $recordId;
                }
                break;

            case self::STRATEGY_DATED:
            default:
                // Add year and month
                $pathParts[] = $year;
                $pathParts[] = $month;
                break;
        }

        return implode('/', $pathParts);
    }

    /**
     * Execute custom callback for path generation
     *
     * @param callable $callback User-provided callback
     * @param string $root Root directory
     * @param Model|null $model Eloquent model
     * @param mixed $recordId Record ID
     * @param string $year Year
     * @param string $month Month
     * @return string Generated path
     */
    protected static function executeCallback(
        callable $callback,
        string $root,
        ?Model $model,
        mixed $recordId,
        string $year,
        string $month
    ): string {
        // Create a simple date object to pass to callback
        $date = (object) [
            'year' => $year,
            'month' => $month,
            'full' => "{$year}/{$month}",
        ];

        $result = call_user_func(
            $callback,
            $model,           // Current model or null
            $recordId,        // Record ID or null
            $root,           // Root directory
            $date            // Date information
        );

        // Ensure result is a string and normalize
        $result = (string) $result;
        $result = ltrim($result, '/');

        // Remove trailing slashes
        return rtrim($result, '/');
    }

    /**
     * Get path with trailing slash for convenient concatenation
     *
     * @param array $options Same as generate()
     * @return string Path with trailing slash
     */
    public static function generateWithSlash(array $options = []): string
    {
        return static::generate($options) . '/';
    }
}
