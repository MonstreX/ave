<?php

namespace Monstrex\Ave\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorizable Contract
 * Defines the interface for authorization checks
 * NOTE: Will be implemented by Resource in PHASE-8
 */
interface Authorizable
{
    /**
     * Check if user can perform ability
     *
     * @param string $ability Ability name (viewAny, view, create, update, delete)
     * @param Authenticatable|null $user Current user
     * @param mixed $model Model instance for singular checks
     * @return bool
     */
    public function authorize(string $ability, ?Authenticatable $user = null, mixed $model = null): bool;
}
