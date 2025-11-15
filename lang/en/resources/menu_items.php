<?php

return [
    'label' => 'Menu Items',
    'singular' => 'Menu Item',

    'filters' => [
        'menu' => 'Menu',
    ],

    'columns' => [
        'title' => 'Title',
        'status' => 'Status',
    ],

    'fields' => [
        'title' => 'Title',
        'icon_class' => 'Icon class',
        'route_name' => 'Route name',
        'route_params' => 'Route parameters (JSON)',
        'url' => 'URL',
        'target' => 'Target',
        'order' => 'Order',
        'parent_id' => 'Parent menu item',
        'menu_id' => 'Menu',
        'status' => 'Status',
    ],

    'help' => [
        'icon_class' => 'Voyager icon class (e.g., voyager-boat)',
        'route_name' => 'Laravel route name',
        'route_params' => 'JSON object with route parameters',
        'url' => 'Direct URL (if not using route)',
        'parent_id' => 'Select to create submenu item',
        'order' => 'Display order',
    ],

    'target_options' => [
        '_self' => 'Same tab',
        '_blank' => 'New tab',
    ],
];
