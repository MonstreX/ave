<?php

namespace Monstrex\Ave\Admin\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Monstrex\Ave\Models\Permission;
use Monstrex\Ave\Models\Role;

class AccessManager
{
    /**
     * @var array<int,string>
     */
    protected array $defaultAbilities;

    protected bool $enabled;
    protected ?string $superRoleSlug;
    protected int $cacheTtl;
    protected bool $fallbackToDefaultRoles;

    protected array $defaultRoleIds = [];

    public function __construct()
    {
        $config = config('ave.acl', []);
        $this->enabled = (bool) ($config['enabled'] ?? true);
        $this->defaultAbilities = $this->normalizeDefinitions(
            $config['default_abilities'] ?? ['viewAny', 'view', 'create', 'update', 'delete']
        );
        $this->superRoleSlug = $config['super_role'] ?? null;
        $this->cacheTtl = (int) ($config['cache_ttl'] ?? 300);
        $this->fallbackToDefaultRoles = (bool) ($config['fallback_to_default_roles'] ?? true);

        $this->defaultRoleIds = Schema::hasTable('ave_roles')
            ? Role::query()->where('is_default', true)->pluck('id')->all()
            : [];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param array<int|string,string|array<string,mixed>> $definitions
     */
    public function registerPermissions(string $resourceSlug, array $definitions = []): void
    {
        if (! $this->enabled || ! Schema::hasTable('ave_permissions')) {
            return;
        }

        if (empty($definitions)) {
            $definitions = $this->defaultAbilities;
        } else {
            $definitions = $this->normalizeDefinitions($definitions);
        }

        foreach ($definitions as $ability => $meta) {
            $permission = Permission::query()->firstOrCreate(
                [
                    'resource_slug' => $resourceSlug,
                    'ability' => $ability,
                ],
                [
                    'name' => $meta['name'] ?? Str::headline($ability),
                    'description' => $meta['description'] ?? null,
                ]
            );

            if ($permission->wasRecentlyCreated && ! empty($this->defaultRoleIds)) {
                $this->attachToDefaultRoles($permission->id);
            }
        }

        // Sync: Remove permissions that are no longer defined for this resource
        $expectedAbilities = array_keys($definitions);
        Permission::query()
            ->where('resource_slug', $resourceSlug)
            ->whereNotIn('ability', $expectedAbilities)
            ->delete();
    }

    protected function attachToDefaultRoles(int $permissionId): void
    {
        if (! Schema::hasTable('ave_permission_role')) {
            return;
        }

        foreach ($this->defaultRoleIds as $roleId) {
            DB::table('ave_permission_role')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                []
            );
        }
    }

    public function allows(?Authenticatable $user, string $resourceSlug, string $ability): bool
    {
        if (! $this->enabled) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($this->superRoleSlug && $this->userHasRole($user, $this->superRoleSlug)) {
            return true;
        }

        if (! $this->tablesExist(['ave_permissions'])) {
            return false;
        }

        $permission = Permission::query()
            ->where('resource_slug', $resourceSlug)
            ->where('ability', $ability)
            ->first();

        if (! $permission) {
            return false;
        }

        if (! $this->tablesExist(['ave_permission_role'])) {
            return false;
        }

        $roleIds = $this->userRoleIds($user);

        if (empty($roleIds)) {
            return false;
        }

        return DB::table('ave_permission_role')
            ->where('permission_id', $permission->id)
            ->whereIn('role_id', $roleIds)
            ->exists();
    }

    /**
     * Bulk check permissions for multiple resource-ability pairs.
     * Optimized for checking many permissions at once (e.g., for menu rendering).
     *
     * @param  Authenticatable|null  $user
     * @param  array<int,array{resource:string,ability:string}>  $checks  Array of ['resource' => 'slug', 'ability' => 'name'] pairs
     * @return array<string,bool>  Keyed by 'resource.ability' => bool
     */
    public function bulkAllows(?Authenticatable $user, array $checks): array
    {
        $result = [];

        // Early returns for common cases
        if (! $this->enabled) {
            foreach ($checks as $check) {
                $key = $check['resource'].'.'.$check['ability'];
                $result[$key] = true;
            }

            return $result;
        }

        if (! $user) {
            foreach ($checks as $check) {
                $key = $check['resource'].'.'.$check['ability'];
                $result[$key] = false;
            }

            return $result;
        }

        // Super role bypass
        if ($this->superRoleSlug && $this->userHasRole($user, $this->superRoleSlug)) {
            foreach ($checks as $check) {
                $key = $check['resource'].'.'.$check['ability'];
                $result[$key] = true;
            }

            return $result;
        }

        if (! $this->tablesExist(['ave_permissions', 'ave_permission_role'])) {
            foreach ($checks as $check) {
                $key = $check['resource'].'.'.$check['ability'];
                $result[$key] = false;
            }

            return $result;
        }

        // Get user's role IDs
        $roleIds = $this->userRoleIds($user);

        if (empty($roleIds)) {
            foreach ($checks as $check) {
                $key = $check['resource'].'.'.$check['ability'];
                $result[$key] = false;
            }

            return $result;
        }

        // Load all permissions matching the checks in one query
        $permissions = Permission::query()
            ->where(function ($query) use ($checks) {
                foreach ($checks as $check) {
                    $query->orWhere(function ($q) use ($check) {
                        $q->where('resource_slug', $check['resource'])
                            ->where('ability', $check['ability']);
                    });
                }
            })
            ->get(['id', 'resource_slug', 'ability']);

        // Build lookup map: 'resource.ability' => permission_id
        $permissionMap = [];
        foreach ($permissions as $permission) {
            $key = $permission->resource_slug.'.'.$permission->ability;
            $permissionMap[$key] = $permission->id;
        }

        // Get all permission-role links for user's roles in one query
        $permissionIds = array_values($permissionMap);
        $allowedPermissionIds = [];

        if (! empty($permissionIds)) {
            $allowedPermissionIds = DB::table('ave_permission_role')
                ->whereIn('permission_id', $permissionIds)
                ->whereIn('role_id', $roleIds)
                ->pluck('permission_id')
                ->all();
        }

        // Build result array
        foreach ($checks as $check) {
            $key = $check['resource'].'.'.$check['ability'];
            $permissionId = $permissionMap[$key] ?? null;
            $result[$key] = $permissionId && in_array($permissionId, $allowedPermissionIds, true);
        }

        return $result;
    }

    /**
     * @param array<int|string,string|array<string,mixed>> $definitions
     * @return array<string,array<string,mixed>>
     */
    protected function normalizeDefinitions(array $definitions): array
    {
        $normalized = [];

        foreach ($definitions as $key => $definition) {
            if (is_array($definition)) {
                $ability = is_string($key) ? $key : ($definition['ability'] ?? null);
                if (! $ability) {
                    continue;
                }

                $normalized[$ability] = $definition;
                continue;
            }

            $ability = is_string($key) ? $key : (string) $definition;
            $normalized[$ability] = [];
        }

        return $normalized;
    }

    /**
     * @return array<int,int>
     */
    protected function userRoleIds(Authenticatable $user): array
    {
        return $this->userRoleCache($user)['ids'];
    }

    protected function userHasRole(Authenticatable $user, string $slug): bool
    {
        return in_array($slug, $this->userRoleCache($user)['slugs'], true);
    }

    /**
     * @return array{ids:array<int,int>,slugs:array<int,string>}
     */
    protected function userRoleCache(Authenticatable $user): array
    {
        if (! $this->enabled || ! $this->tablesExist(['ave_role_user', 'ave_roles'])) {
            return ['ids' => [], 'slugs' => []];
        }

        $key = sprintf('ave:acl:user:%s:roles', $user->getAuthIdentifier());

        return Cache::remember($key, $this->cacheTtl, function () use ($user) {
            $roles = DB::table('ave_role_user')
                ->join('ave_roles', 'ave_roles.id', '=', 'ave_role_user.role_id')
                ->where('ave_role_user.user_id', $user->getAuthIdentifier())
                ->select('ave_roles.id', 'ave_roles.slug')
                ->get();

            // Fallback to default roles only if configured
            if ($roles->isEmpty() && $this->fallbackToDefaultRoles) {
                $roles = DB::table('ave_roles')
                    ->where('is_default', true)
                    ->get(['id', 'slug']);
            }

            return [
                'ids' => $roles->pluck('id')->all(),
                'slugs' => $roles->pluck('slug')->all(),
            ];
        });
    }

    /**
     * Ensure all required ACL tables exist before granting access.
     *
     * @param  array<int,string>  $tables
     */
    protected function tablesExist(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->reportMissingTable($table);
                return false;
            }
        }

        return true;
    }

    protected function reportMissingTable(string $table): void
    {
        if ($this->shouldSuppressTableWarnings()) {
            return;
        }

        Log::critical(sprintf('Ave ACL table [%s] is missing. Access is denied until migrations are run.', $table));
    }

    protected function shouldSuppressTableWarnings(): bool
    {
        return app()->runningInConsole() && ! app()->runningUnitTests();
    }
}
