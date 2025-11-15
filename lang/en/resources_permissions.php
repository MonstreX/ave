<?php

return [
    'label' => 'Permissions',
    'singular' => 'Permission',

    'columns' => [
        'resource_slug' => 'Resource',
        'ability' => 'Ability',
        'name' => 'Name',
        'description' => 'Description',
        'created_at' => 'Created',
    ],

    'fields' => [
        'resource_slug' => 'Resource slug',
        'ability' => 'Ability',
        'name' => 'Display name',
        'description' => 'Description',
    ],
];
