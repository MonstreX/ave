<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;
use Monstrex\Ave\Models\Menu;
use Monstrex\Ave\Models\MenuItem;

class FileManagerMenuSeeder extends Seeder
{
    /**
     * Seed file manager menu item under System menu.
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

        // Create "File Manager" menu item under System
        MenuItem::firstOrCreate(
            [
                'menu_id' => $menu->id,
                'parent_id' => $systemItem->id,
                'route' => 'ave.file-manager.index',
            ],
            [
                'title' => __('ave::seeders.menus.file_manager'),
                'icon' => 'voyager-folder',
                'order' => 40,
            ]
        );
    }
}
