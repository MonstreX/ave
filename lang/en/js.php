<?php

return [
    // Toast messages
    'toast' => [
        'titles' => [
            'success' => 'Success',
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
        ],
        'defaults' => [
            'success' => 'Action completed successfully.',
            'error' => 'Something went wrong. Please try again.',
            'warning' => 'Please review your action.',
            'info' => 'Here is some information.',
        ],
        'container_not_found' => 'Toast container not found. Add <div id="toast-container"></div> to your layout.',
        'parse_error' => 'Failed to parse toast data',
    ],

    // Modals
    'modals' => [
        'delete_confirm' => 'Are you sure?',
        'delete_text' => 'This action cannot be undone.',
        'yes_delete' => 'Yes, delete',
        'cancel' => 'Cancel',
    ],

    // Actions
    'actions' => [
        'processing' => 'Processing...',
        'success' => 'Action completed successfully',
        'error' => 'Failed to perform action',
        'no_selection' => 'Please select at least one item',
        'confirm' => 'Are you sure you want to perform this action?',
    ],

    // File upload
    'upload' => [
        'browse' => 'Browse',
        'drag_drop' => 'or drag and drop',
        'uploading' => 'Uploading...',
        'upload_failed' => 'Upload failed',
        'file_too_large' => 'File is too large',
        'invalid_type' => 'Invalid file type',
        'remove' => 'Remove',
    ],

    // Media field
    'media' => [
        'select' => 'Select Media',
        'change' => 'Change',
        'remove' => 'Remove',
        'no_file' => 'No file selected',
    ],

    // Fieldset
    'fieldset' => [
        'add_item' => 'Add Item',
        'remove_item' => 'Remove',
        'move_up' => 'Move Up',
        'move_down' => 'Move Down',
        'collapse' => 'Collapse',
        'expand' => 'Expand',
    ],

    // Slug field
    'slug' => [
        'generate' => 'Generate from',
        'auto_generate' => 'Auto-generate',
    ],

    // Tree view
    'tree' => [
        'expand_all' => 'Expand All',
        'collapse_all' => 'Collapse All',
        'save_order' => 'Save Order',
        'saving' => 'Saving...',
        'saved' => 'Order saved successfully',
        'error' => 'Failed to save order',
    ],

    // Inline editing
    'inline' => [
        'edit' => 'Edit',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'saving' => 'Saving...',
        'saved' => 'Saved',
        'error' => 'Failed to save',
    ],

    // Rich editor
    'editor' => [
        'bold' => 'Bold',
        'italic' => 'Italic',
        'underline' => 'Underline',
        'link' => 'Link',
        'image' => 'Image',
        'heading' => 'Heading',
        'quote' => 'Quote',
        'list' => 'List',
        'code' => 'Code',
    ],

    // Sortable table
    'sortable' => [
        'drag_to_reorder' => 'Drag to reorder',
        'save_order' => 'Save Order',
        'order_saved' => 'Order saved',
        'order_error' => 'Failed to save order',
    ],

    // Form validation
    'validation' => [
        'required' => 'This field is required',
        'email' => 'Please enter a valid email',
        'url' => 'Please enter a valid URL',
        'number' => 'Please enter a valid number',
        'min' => 'Value must be at least :min',
        'max' => 'Value must be at most :max',
    ],

    // Common
    'common' => [
        'loading' => 'Loading...',
        'saving' => 'Saving...',
        'saved' => 'Saved',
        'error' => 'Error',
        'success' => 'Success',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'close' => 'Close',
        'save' => 'Save',
        'search' => 'Search',
        'filter' => 'Filter',
        'reset' => 'Reset',
        'apply' => 'Apply',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
    ],
];
