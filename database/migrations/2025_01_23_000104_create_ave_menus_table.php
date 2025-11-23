<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ave_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('ave_menus');
    }
};
