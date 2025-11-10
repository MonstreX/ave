<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ave_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('resource_slug');
            $table->string('ability');
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['resource_slug', 'ability'], 'ave_permissions_slug_ability_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ave_permissions');
    }
};
