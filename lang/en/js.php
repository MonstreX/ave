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
        'upload_failed_with_error' => 'Upload failed: :error',
        'upload_failed_with_status' => 'Upload failed with status: :status',
        'upload_error' => 'Upload error occurred',
        'upload_processing_error' => 'Error processing upload response',
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
        'only_one_file' => 'You can only upload 1 file',
        'max_files_reached' => 'Maximum :count files allowed',
        'file_too_large' => 'File ":name" is too large. Maximum size: :size MB',
        'upload_success' => 'File uploaded successfully.',
        'upload_failed' => 'Upload failed: :error',
        'upload_failed_response' => 'Upload failed: Invalid server response',
        'upload_failed_network' => 'Upload failed. Please try again.',
        'upload_failed_unknown' => 'Unknown error',
        'confirm_remove' => 'You are going to remove:',
        'remove_success' => 'File removed successfully.',
        'delete_success' => 'File deleted successfully.',
        'delete_failed' => 'Failed to delete file: :error',
        'delete_failed_network' => 'Failed to delete file. Please try again.',
        'delete_failed_unknown' => 'Unknown error',
        'crop_load_failed' => 'Could not load image for cropping',
        'crop_button' => 'Crop',
        'cropper_not_initialized' => 'Cropper not initialized',
        'invalid_crop_area' => 'Invalid crop area',
        'crop_success' => 'Image cropped successfully',
        'crop_failed' => 'Failed to crop image: :error',
        'properties_saved' => 'Properties saved successfully',
        'save_failed' => 'Failed to save: :error',
        'reorder_success' => 'Files reordered successfully.',
        'reorder_failed' => 'Failed to reorder files.',
        'no_files_selected' => 'No files selected',
        'bulk_delete_success' => ':count file(s) deleted successfully.',
        'bulk_delete_failed' => 'Failed to delete files: :error',
    ],

    // Fieldset
    'fieldset' => [
        'add_item' => 'Add Item',
        'remove_item' => 'Remove',
        'move_up' => 'Move Up',
        'move_down' => 'Move Down',
        'collapse' => 'Collapse',
        'expand' => 'Expand',
        'collapse_all' => 'Collapse All',
        'expand_all' => 'Expand All',
        'toggle_sort' => 'Toggle Sort Mode',
        'max_items_reached' => 'Maximum :count items allowed',
        'min_items_required' => 'Minimum :count items required',
        'deletion_blocked' => 'Deletion Blocked',
        'confirm_delete' => 'Are you sure you want to delete this item?',
        'delete_item' => 'Delete Item',
        'delete_button' => 'Delete',
        'cancel_button' => 'Cancel',
        'ok_button' => 'OK',
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
        'updated' => 'Tree structure updated',
        'error' => 'Failed to save order',
        'update_failed' => 'Failed to update tree',
        'structure_update_failed' => 'Failed to update tree structure',
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
        'order_updated' => 'Order updated successfully',
        'order_error' => 'Failed to save order',
        'order_update_failed' => 'Failed to update order',
        'table_order_failed' => 'Failed to update table order',
        'item_moved' => 'Item moved to new group successfully',
        'item_move_error' => 'Error updating item group',
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

    // Cache
    'cache' => [
        'error' => 'Error clearing cache',
    ],

    // Common
    'common' => [
        'loading' => 'Loading...',
        'loading_form' => 'Loading form...',
        'saving' => 'Saving...',
        'saved' => 'Saved',
        'saved_successfully' => 'Saved successfully',
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
        'failed_to_load_form' => 'Failed to load form: :error',
        'failed_to_save' => 'Failed to save: :error',
        'yes' => 'Yes',
        'no' => 'No',
        'ok' => 'OK',
        'alert' => 'Alert',
        'copied_to_clipboard' => 'Copied to clipboard.',
        'copy_failed' => 'Copy failed. Please try again.',
    ],
];
