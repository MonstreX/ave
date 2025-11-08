<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Monstrex\Ave\Http\Controllers\ResourceController;
use Monstrex\Ave\Http\Controllers\PageController;
use Monstrex\Ave\Http\Controllers\MediaController;
use Monstrex\Ave\Http\Middleware\HandleAveExceptions;
use Monstrex\Ave\Exceptions\ResourceException;

$prefix = config('ave.route_prefix', 'admin');

// Add exception handling middleware first (innermost) so it catches all Ave exceptions
$middleware = [HandleAveExceptions::class];

// Then add other middleware
$middleware = array_merge($middleware, Arr::wrap(config('ave.middleware', ['web'])));

if ($guard = config('ave.auth_guard')) {
    $middleware[] = 'auth:' . $guard;
}

Route::prefix($prefix)
    ->middleware(array_filter($middleware))
    ->group(function () {
        Route::get('/', static function () {
            return view('ave::dashboard');
        })->name('ave.dashboard');

        // Resource routes
        Route::get('/resource/{slug}', [ResourceController::class, 'index'])
            ->name('ave.resource.index');

        Route::get('/resource/{slug}/create', [ResourceController::class, 'create'])
            ->name('ave.resource.create');

        Route::post('/resource/{slug}', [ResourceController::class, 'store'])
            ->name('ave.resource.store');

        Route::get('/resource/{slug}/{id}/edit', [ResourceController::class, 'edit'])
            ->name('ave.resource.edit');

        Route::match(['put', 'patch'], '/resource/{slug}/{id}', [ResourceController::class, 'update'])
            ->name('ave.resource.update');

        Route::delete('/resource/{slug}/{id}', [ResourceController::class, 'destroy'])
            ->name('ave.resource.destroy');

        // API routes for SPA
        Route::get('/resource/{slug}/table.json', [ResourceController::class, 'tableJson'])
            ->name('ave.resource.table.json');

        Route::get('/resource/{slug}/form.json', [ResourceController::class, 'formJson'])
            ->name('ave.resource.form.json');

        // Page routes
        Route::get('/page/{slug}', [PageController::class, 'show'])
            ->name('ave.page.show');

        // Media routes
        Route::post('/media/upload', [MediaController::class, 'upload'])
            ->name('ave.media.upload');

        Route::delete('/media/collection', [MediaController::class, 'destroyCollection'])
            ->name('ave.media.destroy-collection');

        Route::delete('/media/{id}', [MediaController::class, 'destroy'])
            ->name('ave.media.destroy');

        Route::post('/media/reorder', [MediaController::class, 'reorder'])
            ->name('ave.media.reorder');

        Route::post('/media/{id}/props', [MediaController::class, 'updateProps'])
            ->name('ave.media.update-props');

        Route::post('/media/{id}/crop', [MediaController::class, 'cropImage'])
            ->name('ave.media.crop');

        Route::post('/media/bulk-delete', [MediaController::class, 'bulkDestroy'])
            ->name('ave.media.bulk-destroy');

        // Fallback for unmapped admin routes - must be last
        Route::fallback(function () {
            throw ResourceException::notFound('page');
        });
    });
