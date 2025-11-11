<?php

namespace Monstrex\Ave\Admin\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        if (! Schema::hasTable('ave_permissions')) {
            return true;
        }

        $permission = Permission::query()
            ->where('resource_slug', $resourceSlug)
            ->where('ability', $ability)
            ->first();

        if (! $permission) {
            return false;
        }

        if (! Schema::hasTable('ave_permission_role')) {
            return true;
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
        if (! $this->enabled || ! Schema::hasTable('ave_role_user') || ! Schema::hasTable('ave_roles')) {
            return ['ids' => [], 'slugs' => []];
        }

        $key = sprintf('ave:acl:user:%s:roles', $user->getAuthIdentifier());

        return Cache::remember($key, $this->cacheTtl, function () use ($user) {
            $roles = DB::table('ave_role_user')
                ->join('ave_roles', 'ave_roles.id', '=', 'ave_role_user.role_id')
                ->where('ave_role_user.user_id', $user->getAuthIdentifier())
                ->select('ave_roles.id', 'ave_roles.slug')
                ->get();

            if ($roles->isEmpty()) {
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
}
