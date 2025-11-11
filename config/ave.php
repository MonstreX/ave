<?php

return [
    /*
     * Route prefix for all Ave admin endpoints.
     */
    'route_prefix' => env('AVE_ROUTE_PREFIX', 'admin'),

    /*
     * Authentication guard that should be used for Ave routes.
     */
    'auth_guard' => env('AVE_AUTH_GUARD', 'web'),

    /*
     * Middleware stack applied to Ave routes (in addition to the auth guard).
     */
    'middleware' => [
        'web',
    ],

    /*
     * Rate limiting configuration (attempts per minute, throttle signature).
     */
    'rate_limits' => [
        'auth' => env('AVE_RATE_LIMIT_AUTH', '10,1'),
        'api' => env('AVE_RATE_LIMIT_API', '60,1'),
    ],

    /*
     * Unified storage configuration for media and file uploads.
     */
    'storage' => [
        'url_generator' => Monstrex\Ave\Media\Services\URLGeneratorService::class,
        'disk' => env('AVE_STORAGE_DISK', 'public'),
        'root' => env('AVE_STORAGE_ROOT', 'files'),
        'path' => [
            'strategy' => env('AVE_STORAGE_PATH', 'dated'), // flat|dated
        ],
        'filename' => [
            'strategy' => env('AVE_STORAGE_FILENAME', 'transliterate'),
            'separator' => env('AVE_STORAGE_SEPARATOR', '-'),
            'locale' => env('AVE_STORAGE_LOCALE', 'en'),
            'uniqueness' => env('AVE_STORAGE_UNIQUENESS', 'suffix'),
        ],
        'image' => [
            'max_size' => env('AVE_STORAGE_IMAGE_MAX', 2000),
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
