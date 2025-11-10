<?php

return [
    /*
     * Route prefix for all Ave admin endpoints.
     */
    'route_prefix' => env('AVE_ROUTE_PREFIX', 'admin'),

    /*
     * Authentication guard that should be used for Ave routes.
     */
    'auth_guard' => 'web',

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

        /*
         * Path generation strategy for media files.
         * Available strategies:
         * - 'flat': Organize by model and record ID: {root}/{model_table}/{record_id}/
         * - 'dated': Organize by model and date: {root}/{model_table}/{year}/{month}/
         * Can be overridden per field via ->pathStrategy() or ->pathGenerator(callback)
         */
        'path' => [
            'strategy' => env('AVE_MEDIA_PATH_STRATEGY', 'dated'),  // flat|dated
        ],
    ],

    /*
     * File storage configuration (for File field uploads).
     */
    'files' => [
        'root' => env('AVE_FILES_ROOT', 'uploads/files'),
        'disk' => 'public',

        /*
         * Path generation strategy for file field uploads.
         * Available strategies:
         * - 'flat': Organize by model and record ID: {root}/{model_table}/{record_id}/
         * - 'dated': Organize by model and date: {root}/{model_table}/{year}/{month}/
         * Can be overridden per field via ->pathStrategy() or ->pathGenerator(callback)
         */
        'path' => [
            'strategy' => env('AVE_FILES_PATH_STRATEGY', 'dated'),  // flat|dated
        ],

        /*
         * Filename generation strategy for file field uploads.
         * Same options as media.filename (see above).
         */
        'filename' => [
            'strategy' => env('AVE_FILES_FILENAME_STRATEGY', 'transliterate'),
            'separator' => '-',
            'locale' => 'ru',
            'uniqueness' => 'suffix',
        ],
    ],

    'acl' => [
        'enabled' => (bool) env('AVE_ACL_ENABLED', true),
        'default_abilities' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
        ],
        'super_role' => env('AVE_ACL_SUPER_ROLE', 'admin'),
        'cache_ttl' => env('AVE_ACL_CACHE_TTL', 300),
    ],

    'menu' => [
        'default_slug' => env('AVE_MENU_DEFAULT', 'main'),
    ],

    'user_model' => env('AVE_USER_MODEL', config('auth.providers.users.model')),
    'user_table' => env('AVE_USER_TABLE', 'users'),
    'login_route' => 'login',
    'login_submit_route' => 'login.submit',

    /*
     * Discovery cache configuration.
     */
    'cache_discovery' => true,
    'cache_ttl' => 3600,
];
