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
     * Media storage configuration reused from v1 implementation.
     */
    'media' => [
        'url_generator' => Monstrex\Ave\Media\Services\URLGeneratorService::class,
        'storage' => [
            'root' => 'media',
            'disk' => 'public',
        ],
        'transliterations' => [
            'ru' => [
                'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ж' => 'J', 'З' => 'Z', 'И' => 'I',
                'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
                'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'TS', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'YI', 'Ь' => '',
                'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ж' => 'j',
                'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
                'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => 'y',
                'ы' => 'yi', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', ' ' => '_',
            ],
        ],
    ],

    /*
     * Discovery cache configuration.
     */
    'cache_discovery' => true,
    'cache_ttl' => 3600,
];
