<?php

return [
    'title' => 'Storage Link Missing',
    'message' => 'The public storage symlink does not exist or points to the wrong location. This link is required for file uploads and media to work correctly.',
    'create_button' => 'Create Link',
    'success' => 'Storage link created successfully.',
    'error_directory_exists' => 'Cannot create symlink: a directory already exists at public/storage. Please remove it manually.',
    'error_create_failed' => 'Failed to create storage link. Please run "php artisan storage:link" manually.',
    'error_exception' => 'Error creating storage link: :error',
];
