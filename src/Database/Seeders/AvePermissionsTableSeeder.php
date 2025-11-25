<?php

namespace Monstrex\Ave\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Monstrex\Ave\Models\Permission;
use Monstrex\Ave\Models\Role;

class AvePermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('ave_permissions')) {
            return;
        }

        $definitions = [
            [
                'resource' => 'file-manager',
                'ability' => 'viewAny',
                'name' => __('ave::file_manager.permissions.view'),
                'description' => __('ave::file_manager.permissions.view_description'),
            ],
            [
                'resource' => 'file-manager',
                'ability' => 'create',
                'name' => __('ave::file_manager.permissions.create'),
                'description' => __('ave::file_manager.permissions.create_description'),
            ],
            [
                'resource' => 'file-manager',
                'ability' => 'delete',
                'name' => __('ave::file_manager.permissions.delete'),
                'description' => __('ave::file_manager.permissions.delete_description'),
            ],
            [
                'resource' => 'database-manager',
                'ability' => 'browse',
                'name' => __('ave::database.permissions.browse'),
                'description' => __('ave::database.permissions.browse_description'),
            ],
            [
                'resource' => 'database-manager',
                'ability' => 'create',
                'name' => __('ave::database.permissions.create'),
                'description' => __('ave::database.permissions.create_description'),
            ],
            [
                'resource' => 'database-manager',
                'ability' => 'update',
                'name' => __('ave::database.permissions.update'),
                'description' => __('ave::database.permissions.update_description'),
            ],
            [
                'resource' => 'database-manager',
                'ability' => 'delete',
                'name' => __('ave::database.permissions.delete'),
                'description' => __('ave::database.permissions.delete_description'),
            ],
        ];

        $adminRole = null;
        if (Schema::hasTable('ave_roles')) {
            $adminRole = Role::where('slug', 'admin')->first();
        }

        foreach ($definitions as $definition) {
            $permission = Permission::firstOrCreate(
                [
                    'resource_slug' => $definition['resource'],
                    'ability' => $definition['ability'],
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'group' => 'system',
                ]
            );

            if ($adminRole && Schema::hasTable('ave_permission_role')) {
                $exists = DB::table('ave_permission_role')
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $adminRole->id)
                    ->exists();

                if (!$exists) {
                    DB::table('ave_permission_role')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $adminRole->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
