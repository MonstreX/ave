<?php

namespace Monstrex\Ave\View\Composers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Monstrex\Ave\Admin\Access\AccessManager;
use Monstrex\Ave\Models\Menu;
use Monstrex\Ave\Models\MenuItem;
use Monstrex\Ave\Core\ResourceManager;

class SidebarComposer
{
    public function __construct(
        private ResourceManager $resourceManager,
        private AccessManager $accessManager,
    ) {
    }

    public function compose(View $view): void
    {
        $user = ave_auth_user();

        $menuItems = $this->buildMenuItems($user);

        // Fallback to alphabetical resource list when no menu defined
        $resources = $this->resourceCollection();

        $view->with([
            'dashboardRoute' => Route::has('ave.dashboard') ? route('ave.dashboard') : null,
            'menuItems' => $menuItems,
            'resources' => $menuItems ? collect() : $resources,
        ]);
    }

    protected function buildMenuItems(?Authenticatable $user): array
    {
        $menuSlug = config('ave.menu.default_slug', 'main');

        $menu = Menu::query()
            ->where('slug', $menuSlug)
            ->orWhere('is_default', true)
            ->orderByDesc('is_default')
            ->first();

        if (! $menu) {
            return [];
        }

        $items = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->orderBy('order')
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        $grouped = $items->groupBy(fn (MenuItem $item) => $item->parent_id ?? 0);

        return $this->buildTree($grouped, 0, $user);
    }

    /**
     * @param  Collection<int,Collection<int,MenuItem>>  $grouped
     * @return array<int,array<string,mixed>>
     */
    protected function buildTree(Collection $grouped, int $parentId, ?Authenticatable $user): array
    {
        $nodes = [];

        /** @var Collection<int,MenuItem> $items */
        $items = $grouped->get($parentId, collect());

        foreach ($items as $item) {
            if (! $this->canSee($user, $item)) {
                continue;
            }

            if ($item->is_divider) {
                $nodes[] = [
                    'type' => 'divider',
                    'title' => $item->title,
                ];
                continue;
            }

            $children = $this->buildTree($grouped, $item->id, $user);

            $nodes[] = [
                'type' => 'item',
                'title' => $item->title,
                'icon' => $item->icon ?: 'voyager-dot',
                'url' => $this->resolveUrl($item),
                'target' => $item->target ?: '_self',
                'resource_slug' => $item->resource_slug,
                'children' => $children,
            ];
        }

        return $nodes;
    }

    protected function canSee(?Authenticatable $user, MenuItem $item): bool
    {
        if (! $this->accessManager->isEnabled()) {
            return true;
        }

        if (! $item->permission_key && ! $item->resource_slug) {
            return true;
        }

        if ($item->resource_slug && $item->ability) {
            return $this->accessManager->allows($user, $item->resource_slug, $item->ability);
        }

        if ($item->permission_key) {
            [$resource, $ability] = array_pad(explode('.', $item->permission_key), 2, 'viewAny');

            return $this->accessManager->allows($user, $resource, $ability ?? 'viewAny');
        }

        return true;
    }

    protected function resolveUrl(MenuItem $item): string
    {
        if ($item->route && Route::has($item->route)) {
            return route($item->route);
        }

        if ($item->resource_slug) {
            return route('ave.resource.index', ['slug' => $item->resource_slug]);
        }

        if ($item->url) {
            return url($item->url);
        }

        return '#';
    }

    protected function resourceCollection(): Collection
    {
        $resourceEntries = new Collection();

        foreach ($this->resourceManager->all() as $slug => $resourceClass) {
            if (! class_exists($resourceClass)) {
                continue;
            }

            $resourceEntries->push([
                'slug' => $slug,
                'class' => $resourceClass,
                'label' => $resourceClass::getLabel(),
                'icon' => $resourceClass::getIcon() ?: 'voyager-data',
            ]);
        }

        return $resourceEntries->sortBy('label');
    }
}
