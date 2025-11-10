<?php

return [
    /*
     * Route prefix for all Ave admin endpoints.
     */
    'route_prefix' => env('AVE_ROUTE_PREFIX', 'admin'),

    /*
     * Authentication guard that should be used for Ave routes.
     */
    'auth_guard' => env('AVE_AUTH_GUARD'),

    /*
     * Middleware stack applied to Ave routes (in addition to the auth guard).
     */
    'middleware' => [
        'web',
    ],

    /*
     * Media storage configuration.
     */
    'media' => [
        'url_generator' => Monstrex\Ave\Media\Services\URLGeneratorService::class,
        'storage' => [
            'root' => 'media',
            'disk' => 'public',
        ],
        // Global maximum image size (in pixels, by longest side)
        // Used for automatic image scaling on upload
        'max_image_size' => env('AVE_MEDIA_MAX_IMAGE_SIZE', 2000),

        /*
         * Filename generation strategy for all file uploads.
         * Available strategies:
         * - 'original': Keep original filename as-is
         * - 'transliterate': Convert to slug format (e.g., "мой файл.jpg" → "moj-fajl.jpg")
         * - 'unique': Generate completely random unique filename (e.g., "a7f3b9c1e5d4f8a2.jpg")
         */
        'filename' => [
            'strategy' => env('AVE_MEDIA_FILENAME_STRATEGY', 'transliterate'),
            'separator' => '-',  // Used for transliterate strategy
            'locale' => 'ru',    // Used for transliterate strategy
            'uniqueness' => 'suffix',  // 'suffix' (adds -1,-2,-3) or 'replace'
        ],
    ],

    /*
     * Discovery cache configuration.
     */
    'cache_discovery' => true,
    'cache_ttl' => 3600,
];
