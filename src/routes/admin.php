<?php

use Illuminate\Support\Facades\Route;
use Monstrex\Ave\Http\Controllers\ResourceController;
use Monstrex\Ave\Http\Controllers\PageController;

Route::prefix(config('ave.prefix', 'admin'))
    ->middleware(config('ave.middleware', ['web', 'auth']))
    ->group(function () {

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
    });
