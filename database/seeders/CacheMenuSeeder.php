<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;
use Monstrex\Ave\Models\Menu;
use Monstrex\Ave\Models\MenuItem;

class CacheMenuSeeder extends Seeder
{
    /**
     * Seed cache management menu items under System menu.
     */
    public function run(): void
    {
        $menu = Menu::where('key', 'admin')
            ->orWhere('is_default', true)
            ->first();

        if (! $menu) {
            return;
        }

        // Find "System" parent menu item
        $systemItem = MenuItem::where('menu_id', $menu->id)
            ->where('title', 'System')
            ->first();

        if (! $systemItem) {
            return;
        }

        // Create "Clear Cache" submenu under System
        $cacheItem = MenuItem::firstOrCreate(
            [
                'menu_id' => $menu->id,
                'parent_id' => $systemItem->id,
                'title' => __('ave::seeders.menus.clear_cache'),
            ],
            [
                'icon' => 'voyager-bolt',
                'order' => 50,
            ]
        );

        // Cache type menu items
        $cacheTypes = [
            ['key' => 'application', 'icon' => 'voyager-data', 'order' => 1],
            ['key' => 'config', 'icon' => 'voyager-settings', 'order' => 2],
            ['key' => 'route', 'icon' => 'voyager-compass', 'order' => 3],
            ['key' => 'view', 'icon' => 'voyager-browser', 'order' => 4],
            ['key' => 'all', 'icon' => 'voyager-trash', 'order' => 5],
        ];

        foreach ($cacheTypes as $type) {
            MenuItem::firstOrCreate(
                [
                    'menu_id' => $menu->id,
                    'parent_id' => $cacheItem->id,
                    'url' => '#cache-clear-' . $type['key'],
                ],
                [
                    'title' => __('ave::seeders.menus.cache_' . $type['key']),
                    'icon' => $type['icon'],
                    'order' => $type['order'],
                ]
            );
        }
    }
}
