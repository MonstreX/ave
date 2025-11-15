<?php

return [
    'label' => 'Roles',
    'singular' => 'Role',

    'columns' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'is_default' => 'Default',
        'created_at' => 'Created',
    ],

    'fields' => [
        'name' => 'Role Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'is_default' => 'Default role',
        'permissions' => 'Permissions',
    ],

    'help' => [
        'slug' => 'Unique identifier, e.g. admin, editor',
        'is_default' => 'Automatically assigned to new users',
    ],
];
