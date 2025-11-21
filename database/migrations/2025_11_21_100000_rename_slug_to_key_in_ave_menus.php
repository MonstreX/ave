<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ave_menus', function (Blueprint $table) {
            $table->renameColumn('slug', 'key');
        });

        // Update 'main' to 'admin' in existing records
        DB::table('ave_menus')
            ->where('key', 'main')
            ->update(['key' => 'admin']);
    }

    public function down(): void
    {
        // Revert 'admin' back to 'main'
        DB::table('ave_menus')
            ->where('key', 'admin')
            ->update(['key' => 'main']);

        Schema::table('ave_menus', function (Blueprint $table) {
            $table->renameColumn('key', 'slug');
        });
    }
};
