<?php

namespace Monstrex\Ave\Routing\Concerns;

use Illuminate\Routing\Router;
use Monstrex\Ave\Http\Controllers\PageController;
use Monstrex\Ave\Http\Controllers\ResourceController;

trait RegistersCrudRoutes
{
    protected function registerResourceCrudRoutes(Router $router): void
    {
        $router->get('/resource/{slug}', [ResourceController::class, 'index'])
            ->name('ave.resource.index');

        $router->get('/resource/{slug}/create', [ResourceController::class, 'create'])
            ->name('ave.resource.create');

        $router->post('/resource/{slug}', [ResourceController::class, 'store'])
            ->name('ave.resource.store');

        $router->get('/resource/{slug}/{id}/edit', [ResourceController::class, 'edit'])
            ->name('ave.resource.edit');

        $router->match(['put', 'patch'], '/resource/{slug}/{id}', [ResourceController::class, 'update'])
            ->name('ave.resource.update');

        $router->delete('/resource/{slug}/{id}', [ResourceController::class, 'destroy'])
            ->name('ave.resource.destroy');

        $router->get('/resource/{slug}/table.json', [ResourceController::class, 'tableJson'])
            ->name('ave.resource.table.json');

        $router->get('/resource/{slug}/form.json', [ResourceController::class, 'formJson'])
            ->name('ave.resource.form.json');

        $router->post('/resource/{slug}/{id}/actions/{action}', [ResourceController::class, 'runRowAction'])
            ->name('ave.resource.action.row');

        $router->post('/resource/{slug}/actions/{action}/bulk', [ResourceController::class, 'runBulkAction'])
            ->name('ave.resource.action.bulk');

        $router->post('/resource/{slug}/actions/{action}/global', [ResourceController::class, 'runGlobalAction'])
            ->name('ave.resource.action.global');

        $router->post('/resource/{slug}/{id?}/actions/{action}/form', [ResourceController::class, 'runFormAction'])
            ->name('ave.resource.action.form');

        $router->get('/page/{slug}', [PageController::class, 'show'])
            ->name('ave.page.show');
    }
}
