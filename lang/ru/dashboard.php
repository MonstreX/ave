<?php

return [
    'title' => 'Панель управления',

    // Welcome
    'welcome' => 'Добро пожаловать, :name!',
    'welcome_message' => 'Здесь вы можете увидеть текущее состояние системы и техническую информацию.',

    // PHP Info
    'php_info' => 'Информация о PHP',
    'php_version' => 'Версия PHP',
    'memory_limit' => 'Лимит памяти',
    'max_execution_time' => 'Макс. время выполнения',
    'upload_max_filesize' => 'Макс. размер файла',

    // Laravel Info
    'laravel_info' => 'Информация о Laravel',
    'laravel_version' => 'Версия Laravel',
    'environment' => 'Окружение',
    'debug_mode' => 'Режим отладки',
    'timezone' => 'Часовой пояс',

    // Ave Info
    'ave_info' => 'Ave Admin Panel',
    'ave_version' => 'Версия Ave',
    'route_prefix' => 'Префикс роутов',
    'auth_guard' => 'Guard авторизации',
    'storage_disk' => 'Диск хранилища',

    // Database Info
    'database_info' => 'Информация о БД',
    'connection' => 'Подключение',
    'driver' => 'Драйвер',
    'database' => 'База данных',
    'status' => 'Статус',
    'connected' => 'Подключено',
    'disconnected' => 'Отключено',

    // Server Info
    'server_info' => 'Информация о сервере',
    'server_software' => 'ПО сервера',
    'server_os' => 'Операционная система',
    'server_ip' => 'IP сервера',
    'disk_usage' => 'Использование диска',

    // Cache Info
    'cache_info' => 'Информация о кеше',
    'cache_driver' => 'Драйвер кеша',
    'session_driver' => 'Драйвер сессий',
    'queue_driver' => 'Драйвер очередей',

    // Status Badges
    'ok' => 'OK',
    'warning' => 'Внимание',
    'critical' => 'Критично',
    'outdated' => 'Устарело',
    'production' => 'Продакшн',
    'development' => 'Разработка',
    'enabled' => 'Включено',
    'disabled' => 'Выключено',

    // Warnings
    'system_warnings' => 'Системные предупреждения',
    'warning_debug_production' => 'Режим отладки включен на продакшн-окружении! Это угроза безопасности.',
    'warning_php_version' => 'Версия PHP устарела. Пожалуйста, обновитесь до PHP 8.2 или выше.',
    'warning_opcache' => 'OPcache не включен. Рекомендуется включить для улучшения производительности.',
    'warning_disk_space' => 'Критически мало места на диске. Пожалуйста, освободите место.',
];
