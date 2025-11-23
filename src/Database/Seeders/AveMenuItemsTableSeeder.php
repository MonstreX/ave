<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Monstrex\Ave\Models\MenuItem;

class AveMenuItemsTableSeeder extends Seeder
{
    public function run(): void
    {
        $menu = DB::table('ave_menus')->where('key', 'admin')->first();

        if (! $menu) {
            return;
        }

        $order = 1;

        $this->updateOrCreateItem($menu->id, null, __('ave::seeders.menus.dashboard'), [
            'icon' => 'voyager-dashboard',
            'route' => 'ave.dashboard',
            'order' => $order++,
        ]);

        $this->updateOrCreateItem($menu->id, null, __('ave::seeders.menus.file_manager'), [
            'icon' => 'voyager-folder',
            'route' => 'ave.file-manager.index',
            'permission_key' => 'file-manager.viewAny',
            'order' => $order++,
        ]);

        $settings = $this->updateOrCreateItem($menu->id, null, __('ave::seeders.menus.settings'), [
            'icon' => 'voyager-settings',
            'order' => $order++,
        ]);

        $childOrder = 1;
        $settingsChildren = [
            [__('ave::seeders.menus.menus'), 'voyager-list', 'menus'],
            [__('ave::seeders.menus.users'), 'voyager-person', 'users'],
            [__('ave::seeders.menus.roles'), 'voyager-lock', 'roles'],
            [__('ave::seeders.menus.permissions'), 'voyager-key', 'permissions'],
        ];

        foreach ($settingsChildren as [$title, $icon, $slug]) {
            $this->updateOrCreateItem($menu->id, $settings->id, $title, [
                'icon' => $icon,
                'resource_slug' => $slug,
                'ability' => 'viewAny',
                'order' => $childOrder++,
            ]);
        }

        $prefix = trim((string) config('ave.route_prefix', 'admin'), '/');
        $prefix = $prefix === '' ? 'admin' : $prefix;

        $this->updateOrCreateItem($menu->id, $settings->id, __('ave::seeders.menus.icons'), [
            'icon' => 'voyager-compass',
            'url' => '/' . $prefix . '/page/icons',
            'order' => $childOrder++,
        ]);

        $cache = $this->updateOrCreateItem($menu->id, $settings->id, __('ave::seeders.menus.clear_cache'), [
            'icon' => 'voyager-bolt',
            'order' => $childOrder++,
        ]);

        $cacheTypes = [
            ['cache_application', 'voyager-data'],
            ['cache_config', 'voyager-settings'],
            ['cache_route', 'voyager-compass'],
            ['cache_view', 'voyager-browser'],
            ['cache_all', 'voyager-trash'],
        ];

        $cacheOrder = 1;

        foreach ($cacheTypes as [$key, $icon]) {
            $this->updateOrCreateItem($menu->id, $cache->id, __('ave::seeders.menus.' . $key), [
                'icon' => $icon,
                'url' => '#cache-clear-' . str_replace('cache_', '', $key),
                'order' => $cacheOrder++,
            ]);
        }
    }

    protected function updateOrCreateItem(int $menuId, ?int $parentId, string $title, array $attributes): MenuItem
    {
        return MenuItem::updateOrCreate(
            [
                'menu_id' => $menuId,
                'parent_id' => $parentId,
                'title' => $title,
            ],
            $attributes
        );
    }
}
