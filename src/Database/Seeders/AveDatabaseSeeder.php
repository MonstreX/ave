<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;

class AveDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AveRolesTableSeeder::class,
            AvePermissionsTableSeeder::class,
            AveMenusTableSeeder::class,
            AveMenuItemsTableSeeder::class,
        ]);
    }
}
