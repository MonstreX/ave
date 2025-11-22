<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ave_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')
                ->constrained('ave_menus')
                ->cascadeOnDelete();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('ave_menu_items')
                ->cascadeOnDelete();
            $table->string('title');
            $table->boolean('status')->default(true);
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->string('url')->nullable();
            $table->string('target')->default('_self');
            $table->unsignedInteger('order')->default(0);
            $table->string('permission_key')->nullable();
            $table->string('resource_slug')->nullable();
            $table->string('ability')->nullable();
            $table->boolean('is_divider')->default(false);
            $table->timestamps();
        });

        $menuId = DB::table('ave_menus')->where('key', 'admin')->value('id');

        if ($menuId) {
            $order = 1;

            // Dashboard
            DB::table('ave_menu_items')->insert([
                'menu_id' => $menuId,
                'title' => __('ave::seeders.menus.dashboard'),
                'icon' => 'voyager-dashboard',
                'route' => 'ave.dashboard',
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // File Manager
            DB::table('ave_menu_items')->insert([
                'menu_id' => $menuId,
                'title' => __('ave::seeders.menus.file_manager'),
                'icon' => 'voyager-folder',
                'route' => 'ave.file-manager.index',
                'permission_key' => 'file-manager.viewAny',
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Settings parent
            $settingsId = DB::table('ave_menu_items')->insertGetId([
                'menu_id' => $menuId,
                'title' => __('ave::seeders.menus.settings'),
                'icon' => 'voyager-settings',
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Settings children
            $settingsChildren = [
                [__('ave::seeders.menus.menus'), 'voyager-list', 'menus'],
                [__('ave::seeders.menus.users'), 'voyager-person', 'users'],
                [__('ave::seeders.menus.roles'), 'voyager-lock', 'roles'],
                [__('ave::seeders.menus.permissions'), 'voyager-key', 'permissions'],
            ];

            $childOrder = 1;

            foreach ($settingsChildren as [$title, $icon, $slug]) {
                DB::table('ave_menu_items')->insert([
                    'menu_id' => $menuId,
                    'parent_id' => $settingsId,
                    'title' => $title,
                    'icon' => $icon,
                    'resource_slug' => $slug,
                    'ability' => 'viewAny',
                    'order' => $childOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Cache Clear parent
            $cacheId = DB::table('ave_menu_items')->insertGetId([
                'menu_id' => $menuId,
                'parent_id' => $settingsId,
                'title' => __('ave::seeders.menus.clear_cache'),
                'icon' => 'voyager-bolt',
                'order' => $childOrder++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Cache types
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
                    'menu_id' => $menuId,
                    'parent_id' => $cacheId,
                    'title' => __('ave::seeders.menus.' . $key),
                    'icon' => $icon,
                    'url' => '#cache-clear-' . str_replace('cache_', '', $key),
                    'order' => $cacheOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ave_menu_items');
    }
};
