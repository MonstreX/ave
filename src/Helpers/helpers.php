<?php

if (! function_exists('humanFileSize')) {
    /**
     * Convert bytes to human-readable format
     *
     * Removes trailing zeros after decimal point (e.g., "40KB" not "40.0KB")
     */
    function humanFileSize(int $bytes, int $decimals = 1): string
    {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($bytes <= 0) {
            return '0B';
        }
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        $value = $bytes / pow(1024, $factor);
        $formatted = sprintf("%.{$decimals}f", $value);

        // Remove trailing zeros and decimal point if not needed
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted . $sizes[$factor];
    }
}
