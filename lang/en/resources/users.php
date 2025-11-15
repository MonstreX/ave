<?php

return [
    'label' => 'Users',
    'singular' => 'User',

    'columns' => [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'roles' => 'Roles',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'New Password',
        'roles' => 'Roles',
    ],

    'help' => [
        'password' => 'Leave blank to keep current password.',
        'roles' => 'Assign one or more roles to this user',
    ],
];
