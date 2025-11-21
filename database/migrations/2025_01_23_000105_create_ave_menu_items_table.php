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

            DB::table('ave_menu_items')->insert([
                'menu_id' => $menuId,
                'title' => 'Dashboard',
                'icon' => 'voyager-boat',
                'route' => 'ave.dashboard',
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $resources = [
                ['Articles', 'voyager-news', 'articles'],
                ['Categories', 'voyager-folder', 'categories'],
                ['Tags', 'voyager-tag', 'tags'],
            ];

            foreach ($resources as [$title, $icon, $slug]) {
                DB::table('ave_menu_items')->insert([
                    'menu_id' => $menuId,
                    'title' => $title,
                    'icon' => $icon,
                    'resource_slug' => $slug,
                    'ability' => 'viewAny',
                    'order' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $systemId = DB::table('ave_menu_items')->insertGetId([
                'menu_id' => $menuId,
                'title' => 'System',
                'icon' => 'voyager-lock',
                'order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $systemChildren = [
                ['Users', 'voyager-person', 'users'],
                ['Roles', 'voyager-lock', 'roles'],
                ['Permissions', 'voyager-key', 'permissions'],
                ['Menus', 'voyager-list', 'menus'],
                ['Menu Items', 'voyager-list', 'menu-items'],
            ];

            $childOrder = 1;

            foreach ($systemChildren as [$title, $icon, $slug]) {
                DB::table('ave_menu_items')->insert([
                    'menu_id' => $menuId,
                    'parent_id' => $systemId,
                    'title' => $title,
                    'icon' => $icon,
                    'resource_slug' => $slug,
                    'ability' => 'viewAny',
                    'order' => $childOrder++,
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
