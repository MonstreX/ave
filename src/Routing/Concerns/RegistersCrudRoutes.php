<?php

namespace Monstrex\Ave\Routing\Concerns;

use Illuminate\Routing\Router;
use Monstrex\Ave\Http\Controllers\PageController;
use Monstrex\Ave\Http\Controllers\ResourceController;

trait RegistersCrudRoutes
{
    protected function registerResourceCrudRoutes(Router ): void
    {
        ->get('/resource/{slug}', [ResourceController::class, 'index'])
            ->name('ave.resource.index');

        ->get('/resource/{slug}/create', [ResourceController::class, 'create'])
            ->name('ave.resource.create');

        ->post('/resource/{slug}', [ResourceController::class, 'store'])
            ->name('ave.resource.store');

        ->get('/resource/{slug}/{id}/edit', [ResourceController::class, 'edit'])
            ->name('ave.resource.edit');

        ->match(['put', 'patch'], '/resource/{slug}/{id}', [ResourceController::class, 'update'])
            ->name('ave.resource.update');

        ->delete('/resource/{slug}/{id}', [ResourceController::class, 'destroy'])
            ->name('ave.resource.destroy');

        ->get('/resource/{slug}/table.json', [ResourceController::class, 'tableJson'])
            ->name('ave.resource.table.json');

        ->get('/resource/{slug}/form.json', [ResourceController::class, 'formJson'])
            ->name('ave.resource.form.json');

        ->get('/page/{slug}', [PageController::class, 'show'])
            ->name('ave.page.show');
    }
}
