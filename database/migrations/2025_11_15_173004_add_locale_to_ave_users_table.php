<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table = config('ave.users_table', 'users');

        Schema::table($table, function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table = config('ave.users_table', 'users');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
