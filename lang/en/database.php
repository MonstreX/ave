<?php

return [
    // Page titles
    'title' => 'Database Manager',
    'menu_title' => 'Database',

    // Table list
    'table_name' => 'Table Name',
    'table_actions' => 'Actions',
    'create_table' => 'Create New Table',
    'edit_table' => 'Edit Table',
    'delete_table' => 'Delete Table',
    'view_table' => 'View Structure',

    // Table editor
    'editing_table' => 'Editing Table: :table',
    'creating_table' => 'Creating New Table',
    'update_table' => 'Update Table',
    'create_model' => 'Create Model',

    // Columns
    'columns' => 'Columns',
    'table_columns' => 'Table Columns',
    'table_no_columns' => 'No columns defined yet',
    'add_column' => 'Add Column',
    'add_timestamps' => 'Add Timestamps',
    'add_softdeletes' => 'Add Soft Deletes',
    'add_new_column' => 'Add New Column',

    // Column properties
    'name' => 'Name',
    'type' => 'Type',
    'length' => 'Length',
    'not_null' => 'Not Null',
    'unsigned' => 'Unsigned',
    'auto_increment' => 'Auto Increment',
    'index' => 'Index',
    'default' => 'Default',
    'extra' => 'Extra',

    // Table info modal
    'field' => 'Field',
    'null' => 'Null',
    'key' => 'Key',

    // Index types
    'primary' => 'Primary',
    'unique' => 'Unique',

    // Success messages
    'success_create_table' => 'Table ":table" created successfully',
    'success_update_table' => 'Table ":table" updated successfully',
    'success_delete_table' => 'Table ":table" deleted successfully',

    // Error messages
    'edit_table_not_exist' => 'Table does not exist',
    'delete_table_question' => 'Are you sure you want to delete table :table?',
    'delete_table_question_text' => 'Are you sure you want to delete table',
    'delete_table_confirm' => 'Yes, Delete Table',
    'column_already_exists' => 'Column :column already exists',
    'table_has_index' => 'Table already has a primary key',
    'name_warning' => 'Column name cannot be empty',
    'unknown_type' => 'Unknown type',

    // Type categories
    'type_not_supported' => 'This type is not supported by the current database platform',

    // Composite indexes warning
    'no_composites_warning' => 'Warning: This table has composite indexes. Database Manager currently supports only single-column indexes. Composite indexes will be preserved but cannot be edited.',
    'composite_warning' => 'Part of composite index',
];
