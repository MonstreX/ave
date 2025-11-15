<?php

return [
    'label' => 'Разрешения',
    'singular' => 'Разрешение',

    'columns' => [
        'resource_slug' => 'Ресурс',
        'ability' => 'Действие',
        'name' => 'Название',
        'description' => 'Описание',
        'created_at' => 'Создано',
    ],

    'fields' => [
        'resource_slug' => 'Идентификатор ресурса',
        'ability' => 'Действие',
        'name' => 'Отображаемое название',
        'description' => 'Описание',
    ],
];
