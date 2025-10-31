<?php

namespace Monstrex\Ave\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Base Resource class for Ave v2
 * NOTE: In PHASE-8, will implement Authorizable contract
 */
abstract class Resource
{
    /** @var class-string<Model>|null */
    public static ?string $model = null;

    /** @var class-string|null Policy class for authorization */
    public static ?string $policy = null;

    public static ?string $label = null;
    public static ?string $singularLabel = null;
    public static ?string $icon = null;
    public static ?string $group = null;
    public static ?string $slug = null;
    public static ?int $navSort = null;

    /** @var array<string> Relations to eager load on index */
    public static array $with = [];

    /** @var array<string> Relations to eager load count on index */
    public static array $withCount = [];

    /**
     * Build table schema for index page
     *
     * @param mixed $ctx Current request context
     * @return Table Configured table instance
     */
    public static function table($ctx): Table
    {
        if ($t = static::resolveNearby('Table', Table::make(), $ctx)) {
            return $t;
        }
        return Table::make();
    }

    /**
     * Build form schema for create/edit pages
     *
     * @param mixed $ctx Current request context
     * @return Form Configured form instance
     */
    public static function form($ctx): Form
    {
        if ($f = static::resolveNearby('Form', Form::make(), $ctx)) {
            return $f;
        }
        return Form::make();
    }

    /**
     * Apply eager loading to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function applyEagerLoading($query)
    {
        if (!empty(static::$with)) {
            $query->with(static::$with);
        }
        if (!empty(static::$withCount)) {
            $query->withCount(static::$withCount);
        }
        return $query;
    }

    /**
     * Check if user can perform ability on this resource
     * NOTE: In PHASE-8 this will be moved to Authorizable contract
     *
     * @param string $ability Action name (viewAny, view, create, update, delete)
     * @param Authenticatable|null $user Current user
     * @param mixed $model Model instance for singular checks (view, update, delete)
     * @return bool
     */
    public function authorize(string $ability, ?Authenticatable $user = null, mixed $model = null): bool
    {
        if (!static::$policy) {
            return true; // No policy = allowed
        }

        if (!$user) {
            return false;
        }

        return Gate::forUser($user)->allows($ability, $model ?? static::$model);
    }

    /**
     * Resolve nearby class (Table or Form) if exists
     *
     * @param string $name Class name to look for
     * @param mixed $instance Default instance
     * @param mixed $ctx Context to pass
     * @return mixed
     */
    protected static function resolveNearby(string $name, $instance, $ctx)
    {
        $ns  = static::class;
        $cls = preg_replace('/\\\\Resource$/', "\\\\{$name}", $ns);

        if ($cls && class_exists($cls) && method_exists($cls, 'define')) {
            return $cls::define($instance, $ctx);
        }

        return null;
    }

    /**
     * Get resource slug
     */
    public static function getSlug(): string
    {
        return static::$slug ?? strtolower(class_basename(static::class));
    }

    /**
     * Get resource label
     */
    public static function getLabel(): string
    {
        return static::$label ?? class_basename(static::class);
    }
}
