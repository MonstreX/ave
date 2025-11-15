<?php

namespace Monstrex\Ave\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Monstrex\Ave\Models\Role;

/**
 * HasAveRoles Trait
 *
 * Adds role and permission relationship methods to User model.
 * Enables checking user roles and permissions through Ave's ACL system.
 *
 * Usage:
 *   class User extends Authenticatable {
 *       use HasAveRoles;
 *   }
 *
 * Then you can use:
 *   $user->roles - Get all user roles
 *   $user->hasRole('admin') - Check if user has specific role
 *   $user->hasAnyRole(['admin', 'editor']) - Check if user has any of given roles
 */
trait HasAveRoles
{
    /**
     * Get all roles assigned to this user
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'ave_role_user')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role by slug
     *
     * @param string $slug Role slug (e.g., 'admin', 'editor')
     * @return bool
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Check if user has any of the given roles
     *
     * @param array<int,string> $slugs Array of role slugs
     * @return bool
     */
    public function hasAnyRole(array $slugs): bool
    {
        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    /**
     * Check if user has all of the given roles
     *
     * @param array<int,string> $slugs Array of role slugs
     * @return bool
     */
    public function hasAllRoles(array $slugs): bool
    {
        $userRoles = $this->roles()->whereIn('slug', $slugs)->pluck('slug')->all();

        return count($userRoles) === count($slugs);
    }

    /**
     * Assign a role to this user
     *
     * @param string|int|Role $role Role slug, ID, or Role model
     * @return static
     */
    public function assignRole(string|int|Role $role): static
    {
        if ($role instanceof Role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        } elseif (is_numeric($role)) {
            $this->roles()->syncWithoutDetaching([(int) $role]);
        } else {
            $roleModel = Role::query()->where('slug', $role)->first();
            if ($roleModel) {
                $this->roles()->syncWithoutDetaching([$roleModel->id]);
            }
        }

        return $this;
    }

    /**
     * Remove a role from this user
     *
     * @param string|int|Role $role Role slug, ID, or Role model
     * @return static
     */
    public function removeRole(string|int|Role $role): static
    {
        if ($role instanceof Role) {
            $this->roles()->detach($role->id);
        } elseif (is_numeric($role)) {
            $this->roles()->detach((int) $role);
        } else {
            $roleModel = Role::query()->where('slug', $role)->first();
            if ($roleModel) {
                $this->roles()->detach($roleModel->id);
            }
        }

        return $this;
    }

    /**
     * Sync user roles (replaces all existing roles)
     *
     * @param array<int,string|int|Role> $roles Array of role slugs, IDs, or Role models
     * @return static
     */
    public function syncRoles(array $roles): static
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $roleIds[] = $role->id;
            } elseif (is_numeric($role)) {
                $roleIds[] = (int) $role;
            } else {
                $roleModel = Role::query()->where('slug', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            }
        }

        $this->roles()->sync($roleIds);

        return $this;
    }
}
