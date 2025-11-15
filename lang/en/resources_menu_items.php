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
        'active' => 'Active',
        'target' => 'Target',
    ],

    'fields' => [
        'title' => 'Title',
        'icon' => 'Icon class',
        'icon_class' => 'Icon class',
        'route' => 'Route name',
        'route_name' => 'Route name',
        'route_params' => 'Route parameters (JSON)',
        'url' => 'Custom URL',
        'target' => 'Target',
        'order' => 'Order',
        'parent_id' => 'Parent menu item',
        'menu_id' => 'Menu',
        'status' => 'Active',
        'resource_slug' => 'Resource slug',
        'ability' => 'Ability',
        'permission_key' => 'Permission key',
        'is_divider' => 'Divider',
    ],

    'help' => [
        'icon_class' => 'Voyager icon class (e.g., voyager-boat)',
        'route' => 'Laravel route name, e.g. ave.resource.index',
        'route_name' => 'Laravel route name',
        'route_params' => 'JSON object with route parameters',
        'url' => 'Overrides route if provided',
        'parent_id' => 'Select to create submenu item',
        'order' => 'Display order',
        'resource_slug' => 'Automatically links to resource index',
        'ability' => 'Used with resource slug',
    ],

    'options' => [
        'same_tab' => 'Same tab',
        'new_tab' => 'New tab',
    ],

    'target_options' => [
        '_self' => 'Same tab',
        '_blank' => 'New tab',
    ],
];
