<?php

return [
    // Menu items
    'menu_title' => 'Clear Cache',
    'application' => 'Application Cache',
    'config' => 'Config Cache',
    'route' => 'Route Cache',
    'view' => 'View Cache',
    'all' => 'Clear All',

    // Descriptions
    'application_desc' => 'Clear application cache (sessions, data)',
    'config_desc' => 'Clear configuration cache',
    'route_desc' => 'Clear route cache',
    'view_desc' => 'Clear compiled views cache',
    'all_desc' => 'Clear all caches at once',

    // Messages
    'cleared_application' => 'Application cache cleared successfully',
    'cleared_config' => 'Config cache cleared successfully',
    'cleared_route' => 'Route cache cleared successfully',
    'cleared_view' => 'View cache cleared successfully',
    'cleared_all' => 'All caches cleared successfully',
    'cleared_partial' => 'Some caches could not be cleared',
    'unknown_type' => 'Unknown cache type',
    'error' => 'Error clearing cache',
];
