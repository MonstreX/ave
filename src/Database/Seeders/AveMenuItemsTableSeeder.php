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

        $this->upsertMenuItem([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'route' => 'ave.dashboard',
        ], [
            'title' => __('ave::seeders.menus.dashboard'),
            'icon' => 'voyager-dashboard',
            'status' => 1,
            'url' => null,
            'target' => '_self',
            'order' => $order++,
            'permission_key' => null,
            'resource_slug' => null,
            'ability' => null,
        ]);

        $this->upsertMenuItem([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'route' => 'ave.file-manager.index',
        ], [
            'title' => __('ave::seeders.menus.file_manager'),
            'icon' => 'voyager-folder',
            'status' => 1,
            'url' => null,
            'target' => '_self',
            'order' => $order++,
            'permission_key' => 'file-manager.viewAny',
            'resource_slug' => null,
            'ability' => null,
        ]);

        $settings = $this->upsertMenuItem([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'route' => null,
            'url' => null,
            'resource_slug' => null,
            'icon' => 'voyager-settings',
        ], [
            'title' => __('ave::seeders.menus.settings'),
            'status' => 1,
            'target' => '_self',
            'order' => $order++,
            'permission_key' => null,
            'ability' => null,
        ]);

        $childOrder = 1;
        $settingsChildren = [
            [
                'slug' => 'menus',
                'icon' => 'voyager-list',
                'title' => __('ave::seeders.menus.menus'),
            ],
            [
                'slug' => 'users',
                'icon' => 'voyager-person',
                'title' => __('ave::seeders.menus.users'),
            ],
            [
                'slug' => 'roles',
                'icon' => 'voyager-lock',
                'title' => __('ave::seeders.menus.roles'),
            ],
            [
                'slug' => 'permissions',
                'icon' => 'voyager-key',
                'title' => __('ave::seeders.menus.permissions'),
            ],
        ];

        foreach ($settingsChildren as $child) {
            $this->upsertMenuItem([
                'menu_id' => $menu->id,
                'parent_id' => $settings->id,
                'resource_slug' => $child['slug'],
            ], [
                'title' => $child['title'],
                'icon' => $child['icon'],
                'status' => 1,
                'route' => null,
                'url' => null,
                'target' => '_self',
                'order' => $childOrder++,
                'permission_key' => null,
                'ability' => 'viewAny',
            ]);
        }

        $prefix = trim((string) config('ave.route_prefix', 'admin'), '/');
        $prefix = $prefix === '' ? 'admin' : $prefix;
        $compassUrl = '/' . $prefix . '/page/icons';

        $this->upsertMenuItem([
            'menu_id' => $menu->id,
            'parent_id' => $settings->id,
            'url' => $compassUrl,
        ], [
            'title' => __('ave::seeders.menus.icons'),
            'icon' => 'voyager-compass',
            'status' => 1,
            'route' => null,
            'resource_slug' => null,
            'target' => '_self',
            'order' => $childOrder++,
            'permission_key' => null,
            'ability' => null,
        ]);

        $cache = $this->upsertMenuItem([
            'menu_id' => $menu->id,
            'parent_id' => $settings->id,
            'resource_slug' => null,
            'route' => null,
            'url' => null,
            'icon' => 'voyager-bolt',
        ], [
            'title' => __('ave::seeders.menus.clear_cache'),
            'status' => 1,
            'target' => '_self',
            'order' => $childOrder++,
            'permission_key' => null,
            'ability' => null,
        ]);

        $cacheTypes = [
            ['key' => 'cache_application', 'icon' => 'voyager-data'],
            ['key' => 'cache_config', 'icon' => 'voyager-settings'],
            ['key' => 'cache_route', 'icon' => 'voyager-compass'],
            ['key' => 'cache_view', 'icon' => 'voyager-browser'],
            ['key' => 'cache_all', 'icon' => 'voyager-trash'],
        ];

        $cacheOrder = 1;

        foreach ($cacheTypes as $cacheItem) {
            $anchor = '#cache-clear-' . str_replace('cache_', '', $cacheItem['key']);

            $this->upsertMenuItem([
                'menu_id' => $menu->id,
                'parent_id' => $cache->id,
                'url' => $anchor,
            ], [
                'title' => __('ave::seeders.menus.' . $cacheItem['key']),
                'icon' => $cacheItem['icon'],
                'status' => 1,
                'route' => null,
                'resource_slug' => null,
                'target' => '_self',
                'order' => $cacheOrder++,
                'permission_key' => null,
                'ability' => null,
            ]);
        }
    }

    protected function upsertMenuItem(array $criteria, array $attributes): MenuItem
    {
        if (!isset($criteria['menu_id'])) {
            throw new \InvalidArgumentException('menu_id is required for menu item criteria.');
        }

        $criteria = array_merge([
            'parent_id' => null,
            'route' => null,
            'url' => null,
            'resource_slug' => null,
        ], $criteria);

        return MenuItem::updateOrCreate(
            $criteria,
            array_merge(
                ['menu_id' => $criteria['menu_id']],
                $attributes
            )
        );
    }
}
