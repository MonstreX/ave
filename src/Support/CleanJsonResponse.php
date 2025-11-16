<?php

namespace Monstrex\Ave\Support;

use Illuminate\Http\JsonResponse;

/**
 * Clean JSON Response Handler
 *
 * Filters out PHP warnings, errors, and other garbage that may have been
 * output before JSON response (e.g., from autoloader warnings).
 *
 * Uses output buffering to capture and strip unwanted content, ensuring
 * clean JSON responses for AJAX requests.
 */
class CleanJsonResponse
{
    /**
     * Create a clean JSON response
     *
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code
     * @param array $headers Additional headers
     * @param int $options JSON encoding options
     * @return JsonResponse
     */
    public static function make(
        mixed $data = [],
        int $status = 200,
        array $headers = [],
        int $options = 0
    ): JsonResponse {
        // Clean any existing output before creating response
        self::cleanOutputBuffer();

        // Create the JSON response
        return response()->json($data, $status, $headers, $options);
    }

    /**
     * Clean existing output buffers
     *
     * Removes all buffered output that may contain PHP warnings/errors
     */
    public static function cleanOutputBuffer(): void
    {
        // Get current buffer level
        $level = ob_get_level();

        // Clean all output buffers that contain garbage
        while ($level > 0 && ob_get_level() > 0) {
            $content = ob_get_contents();

            // If buffer contains garbage, clean it
            if (self::isGarbageOutput($content)) {
                ob_clean();
            }

            $level--;

            // Don't go deeper than one level to avoid breaking Laravel's buffers
            break;
        }
    }

    /**
     * Determine if output is garbage (PHP warnings, errors, HTML, etc.)
     *
     * @param string|false $content Output buffer content
     * @return bool True if content should be discarded
     */
    protected static function isGarbageOutput(string|false $content): bool
    {
        if ($content === false || $content === '') {
            return false;
        }

        $content = trim($content);

        if (empty($content)) {
            return false;
        }

        // Patterns that indicate garbage output
        $garbagePatterns = [
            // PHP warnings/errors
            '/^(PHP )?(Warning|Notice|Error|Deprecated|Fatal error):/mi',
            '/(Warning|Notice|Error|Deprecated|Fatal error):/mi',

            // Stack traces
            '/Stack trace:/mi',
            '/^#\d+/m',

            // HTML error output
            '/<br\s*\/?>/i',
            '/<b>Warning<\/b>/i',
            '/<b>Notice<\/b>/i',
            '/<b>Error<\/b>/i',
            '/<b>Deprecated<\/b>/i',

            // Common error file paths
            '/vendor\/sebastian\/version/i',
            '/vendor\/nunomaduro\/collision/i',

            // proc_open specific
            '/proc_open\(\)/i',
            '/CreateProcess failed/i',
        ];

        foreach ($garbagePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        // If content starts with "{" or "[", it's JSON - NOT garbage
        if (preg_match('/^\s*[\[{]/', $content)) {
            return false;
        }

        return false;
    }
}
