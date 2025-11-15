<?php

return [
    'label' => 'Пункты меню',
    'singular' => 'Пункт меню',

    'filters' => [
        'menu' => 'Меню',
    ],

    'columns' => [
        'title' => 'Заголовок',
        'status' => 'Статус',
    ],

    'fields' => [
        'title' => 'Заголовок',
        'icon_class' => 'Класс иконки',
        'route_name' => 'Название маршрута',
        'route_params' => 'Параметры маршрута (JSON)',
        'url' => 'URL',
        'target' => 'Цель',
        'order' => 'Порядок',
        'parent_id' => 'Родительский пункт меню',
        'menu_id' => 'Меню',
        'status' => 'Статус',
    ],

    'help' => [
        'icon_class' => 'Класс иконки Voyager (например, voyager-boat)',
        'route_name' => 'Название маршрута Laravel',
        'route_params' => 'JSON объект с параметрами маршрута',
        'url' => 'Прямой URL (если не используется маршрут)',
        'parent_id' => 'Выберите для создания подменю',
        'order' => 'Порядок отображения',
    ],

    'target_options' => [
        '_self' => 'Та же вкладка',
        '_blank' => 'Новая вкладка',
    ],
];
