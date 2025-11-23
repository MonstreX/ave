<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

    }

    public function down(): void
    {
        Schema::dropIfExists('ave_menu_items');
    }
};
