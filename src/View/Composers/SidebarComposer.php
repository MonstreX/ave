<?php

namespace Monstrex\Ave\View\Composers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Monstrex\Ave\Core\ResourceManager;

class SidebarComposer
{
    public function __construct(private ResourceManager $resourceManager)
    {
    }

    public function compose(View $view): void
    {
        $resourceEntries = new Collection();

        foreach ($this->resourceManager->all() as $slug => $resourceClass) {
            if (!class_exists($resourceClass)) {
                continue;
            }

            $resourceEntries->push([
                'slug' => $slug,
                'class' => $resourceClass,
                'label' => $resourceClass::getLabel(),
                'icon' => $resourceClass::getIcon() ?: 'voyager-data',
                'group' => $resourceClass::getGroup(),
                'sort' => $resourceClass::getNavSort(),
            ]);
        }

        $groupedResources = $resourceEntries
            ->sortBy([
                ['group', 'asc'],
                ['sort', 'asc'],
                ['label', 'asc'],
            ])
            ->groupBy('group');

        $view->with([
            'dashboardRoute' => Route::has('ave.dashboard') ? route('ave.dashboard') : null,
            'groupedResources' => $groupedResources,
        ]);
    }
}

