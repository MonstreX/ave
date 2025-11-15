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
        'active' => 'Активно',
        'target' => 'Цель',
    ],

    'fields' => [
        'title' => 'Заголовок',
        'icon' => 'Класс иконки',
        'icon_class' => 'Класс иконки',
        'route' => 'Название маршрута',
        'route_name' => 'Название маршрута',
        'route_params' => 'Параметры маршрута (JSON)',
        'url' => 'Пользовательский URL',
        'target' => 'Цель',
        'order' => 'Порядок',
        'parent_id' => 'Родительский пункт меню',
        'menu_id' => 'Меню',
        'status' => 'Активно',
        'resource_slug' => 'Slug ресурса',
        'ability' => 'Разрешение',
        'permission_key' => 'Ключ разрешения',
        'is_divider' => 'Разделитель',
    ],

    'help' => [
        'icon_class' => 'Класс иконки Voyager (например, voyager-boat)',
        'route' => 'Название маршрута Laravel, например ave.resource.index',
        'route_name' => 'Название маршрута Laravel',
        'route_params' => 'JSON объект с параметрами маршрута',
        'url' => 'Переопределяет маршрут, если указан',
        'parent_id' => 'Выберите для создания подменю',
        'order' => 'Порядок отображения',
        'resource_slug' => 'Автоматически создает ссылку на индекс ресурса',
        'ability' => 'Используется со slug ресурса',
    ],

    'options' => [
        'same_tab' => 'Та же вкладка',
        'new_tab' => 'Новая вкладка',
    ],

    'target_options' => [
        '_self' => 'Та же вкладка',
        '_blank' => 'Новая вкладка',
    ],
];
