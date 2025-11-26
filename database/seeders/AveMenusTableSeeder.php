<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AveMenusTableSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('ave_menus')->where('key', 'admin')->exists()) {
            return;
        }

        DB::table('ave_menus')->insert([
            'name' => __('ave::seeders.menus.main_name'),
            'key' => 'admin',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
