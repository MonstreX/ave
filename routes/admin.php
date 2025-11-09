<?php

use Illuminate\Support\Facades\Route;
use Monstrex\Ave\Core\Controllers\ResourceController;
use Monstrex\Ave\Core\Controllers\PageController;
use Monstrex\Ave\Http\Controllers\Api\SlugController;

/**
 * Ave Admin Routes
 *
 * Routes for resource management and standalone pages
 */

Route::prefix('admin')->name('admin.')->group(function () {
    // API routes
    Route::prefix('ave/api')->name('api.')->group(function () {
        Route::post('/slug', [SlugController::class, 'generate'])->name('slug');
    });

    // Resource routes
    Route::prefix('resources/{slug}')->name('resources.')->group(function () {
        // List resources
        Route::get('/', [ResourceController::class, 'index'])->name('index');

        // Show create form
        Route::get('/create', [ResourceController::class, 'create'])->name('create');

        // Store new resource
        Route::post('/', [ResourceController::class, 'store'])->name('store');

        // Show edit form
        Route::get('/{id}/edit', [ResourceController::class, 'edit'])->name('edit');

        // Update resource
        Route::put('/{id}', [ResourceController::class, 'update'])->name('update');
        Route::patch('/{id}', [ResourceController::class, 'update'])->name('patch');

        // Delete resource
        Route::delete('/{id}', [ResourceController::class, 'destroy'])->name('destroy');

        // Execute row action
        Route::post('/{id}/action', [ResourceController::class, 'executeAction'])->name('action');

        // Execute bulk action
        Route::post('/bulk-action', [ResourceController::class, 'executeBulkAction'])->name('bulk-action');
    });

    // Page routes
    Route::prefix('pages/{slug}')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'show'])->name('show');
    });
});
