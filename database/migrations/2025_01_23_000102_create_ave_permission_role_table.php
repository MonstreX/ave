<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ave_permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')
                ->constrained('ave_roles')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('ave_permissions')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id'], 'ave_permission_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ave_permission_role');
    }
};
