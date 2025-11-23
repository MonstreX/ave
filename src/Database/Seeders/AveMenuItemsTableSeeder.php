<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AveMenuItemsTableSeeder extends Seeder
{
    public function run(): void
    {
        $menu = DB::table('ave_menus')->where('key', 'admin')->first();

        if (! $menu) {
            return;
        }

        if (DB::table('ave_menu_items')->where('menu_id', $menu->id)->exists()) {
            return;
        }

        $now = now();
        $order = 1;

        DB::table('ave_menu_items')->insert([
            'menu_id' => $menu->id,
            'title' => __('ave::seeders.menus.dashboard'),
            'icon' => 'voyager-dashboard',
            'route' => 'ave.dashboard',
            'order' => $order++,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('ave_menu_items')->insert([
            'menu_id' => $menu->id,
            'title' => __('ave::seeders.menus.file_manager'),
            'icon' => 'voyager-folder',
            'route' => 'ave.file-manager.index',
            'permission_key' => 'file-manager.viewAny',
            'order' => $order++,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $settingsId = DB::table('ave_menu_items')->insertGetId([
            'menu_id' => $menu->id,
            'title' => __('ave::seeders.menus.settings'),
            'icon' => 'voyager-settings',
            'order' => $order++,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $childOrder = 1;
        $settingsChildren = [
            [__('ave::seeders.menus.menus'), 'voyager-list', 'menus'],
            [__('ave::seeders.menus.users'), 'voyager-person', 'users'],
            [__('ave::seeders.menus.roles'), 'voyager-lock', 'roles'],
            [__('ave::seeders.menus.permissions'), 'voyager-key', 'permissions'],
        ];

        foreach ($settingsChildren as [$title, $icon, $slug]) {
            DB::table('ave_menu_items')->insert([
                'menu_id' => $menu->id,
                'parent_id' => $settingsId,
                'title' => $title,
                'icon' => $icon,
                'resource_slug' => $slug,
                'ability' => 'viewAny',
                'order' => $childOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $prefix = trim((string) config('ave.route_prefix', 'admin'), '/');
        $prefix = $prefix === '' ? 'admin' : $prefix;

        DB::table('ave_menu_items')->insert([
            'menu_id' => $menu->id,
            'parent_id' => $settingsId,
            'title' => __('ave::seeders.menus.icons'),
            'icon' => 'voyager-compass',
            'url' => '/' . $prefix . '/page/icons',
            'order' => $childOrder++,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cacheId = DB::table('ave_menu_items')->insertGetId([
            'menu_id' => $menu->id,
            'parent_id' => $settingsId,
            'title' => __('ave::seeders.menus.clear_cache'),
            'icon' => 'voyager-bolt',
            'order' => $childOrder++,
            'created_at' => $now,
            'updated_at' => $now,
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
            DB::table('ave_menu_items')->insert([
                'menu_id' => $menu->id,
                'parent_id' => $cacheId,
                'title' => __('ave::seeders.menus.' . $key),
                'icon' => $icon,
                'url' => '#cache-clear-' . str_replace('cache_', '', $key),
                'order' => $cacheOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
