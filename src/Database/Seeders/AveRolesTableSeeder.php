<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AveRolesTableSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('ave_roles')->where('slug', 'admin')->exists()) {
            return;
        }

        DB::table('ave_roles')->insert([
            'name' => __('ave::seeders.roles.admin_name'),
            'slug' => 'admin',
            'description' => __('ave::seeders.roles.admin_description'),
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
