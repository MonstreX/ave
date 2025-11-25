<?php

return [
    // Заголовки страниц
    'title' => 'Менеджер базы данных',
    'menu_title' => 'База данных',

    // Список таблиц
    'table_name' => 'Название таблицы',
    'table_actions' => 'Действия',
    'create_table' => 'Создать таблицу',
    'edit_table' => 'Редактировать таблицу',
    'delete_table' => 'Удалить таблицу',
    'view_table' => 'Посмотреть структуру',

    // Редактор таблицы
    'editing_table' => 'Редактирование таблицы: :table',
    'creating_table' => 'Создание новой таблицы',
    'update_table' => 'Обновить таблицу',
    'create_model' => 'Создать модель',

    // Столбцы
    'columns' => 'Столбцы',
    'table_columns' => 'Столбцы таблицы',
    'table_no_columns' => 'Столбцы пока не добавлены',
    'add_column' => 'Добавить столбец',
    'add_timestamps' => 'Добавить время создания/изменения',
    'add_softdeletes' => 'Добавить Soft Deletes',
    'add_new_column' => 'Добавить новый столбец',

    // Свойства столбца
    'name' => 'Название',
    'type' => 'Тип',
    'length' => 'Длина',
    'not_null' => 'NOT NULL',
    'unsigned' => 'UNSIGNED',
    'auto_increment' => 'AUTO_INCREMENT',
    'index' => 'Индекс',
    'default' => 'Значение по умолчанию',
    'extra' => 'Дополнительно',

    // Информация о таблице
    'field' => 'Поле',
    'null' => 'Null',
    'key' => 'Ключ',

    // Типы индексов
    'primary' => 'Первичный',
    'unique' => 'Уникальный',

    // Сообщения об успехе
    'success_create_table' => 'Таблица ":table" успешно создана',
    'success_update_table' => 'Таблица ":table" успешно обновлена',
    'success_delete_table' => 'Таблица ":table" успешно удалена',

    // Сообщения об ошибках
    'edit_table_not_exist' => 'Таблица не существует',
    'delete_table_question' => 'Вы уверены, что хотите удалить таблицу :table?',
    'delete_table_question_text' => 'Вы уверены, что хотите удалить таблицу',
    'delete_table_confirm' => 'Да, удалить таблицу',
    'column_already_exists' => 'Столбец :column уже существует',
    'table_has_index' => 'В таблице уже есть первичный ключ',
    'name_warning' => 'Имя столбца не может быть пустым',
    'unknown_type' => 'Неизвестный тип',
    'remove_column_title' => 'Удаление столбца',
    'remove_column_body' => 'Удалить столбец ":column"?',
    'remove_column_confirm' => 'Удалить столбец',

    // Категории типов
    'type_not_supported' => 'Этот тип не поддерживается используемой СУБД',

    // Предупреждения о составных индексах
    'no_composites_warning' => 'В таблице есть составные индексы. Менеджер БД поддерживает только одностолбцовые индексы — существующие составные индексы сохранятся, но их нельзя редактировать.',
    'composite_warning' => 'Столбец входит в составной индекс',

    // Права
    'permissions' => [
        'browse' => 'Доступ к менеджеру БД',
        'browse_description' => 'Просмотр списка таблиц и их структуры',
        'create' => 'Создание таблиц',
        'create_description' => 'Создание новых таблиц и генерация моделей',
        'update' => 'Изменение таблиц',
        'update_description' => 'Редактирование столбцов, индексов и опций',
        'delete' => 'Удаление таблиц',
        'delete_description' => 'Удаление таблиц из базы данных',
    ],
];
