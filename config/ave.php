<?php

return [
    /*
     * Route prefix for all Ave admin endpoints.
     */
    'route_prefix' => env('AVE_ROUTE_PREFIX', 'admin'),

    /*
     * Authentication guard that should be used for Ave routes.
     * Default: 'web' - uses standard Laravel authentication.
     */
    'auth_guard' => 'web',

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
        // When true, users without assigned roles will inherit permissions from roles marked as is_default=true
        // Set to false for stricter security (users must have explicit role assignments)
        'fallback_to_default_roles' => (bool) env('AVE_ACL_FALLBACK_DEFAULT_ROLES', true),
    ],

    'menu' => [
        'default_slug' => env('AVE_MENU_DEFAULT', 'main'),
    ],

    /*
     * Pagination configuration for resource tables.
     */
    'pagination' => [
        'default_per_page' => (int) env('AVE_PAGINATION_DEFAULT_PER_PAGE', 25),
        'per_page_options' => [10, 25, 50, 100],
        'show_per_page_selector' => (bool) env('AVE_PAGINATION_SHOW_SELECTOR', true),
    ],

    /*
     * Validation configuration.
     */
    'validation' => [
        // Maximum number of validation errors to display in toast/flash messages.
        // Remaining errors will be shown as "... and X more error(s)"
        'max_errors_display' => (int) env('AVE_VALIDATION_MAX_ERRORS', 3),
    ],

    /*
     * Field configuration.
     */
    'fields' => [
        // Maximum number of items to load for hierarchical selects (BelongsToSelect with hierarchical())
        // Prevents N+1 queries and memory issues with large datasets
        'hierarchical_max_items' => (int) env('AVE_HIERARCHICAL_MAX_ITEMS', 1000),
    ],

    'user_model' => env('AVE_USER_MODEL', config('auth.providers.users.model')),
    'user_table' => env('AVE_USER_TABLE', 'users'),
    'login_route' => 'login',
    'login_submit_route' => 'login.submit',
];
