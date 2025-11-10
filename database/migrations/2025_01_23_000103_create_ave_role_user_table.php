<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ave_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')
                ->constrained('ave_roles')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id'], 'ave_role_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ave_role_user');
    }
};
