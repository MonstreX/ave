<?php

namespace Monstrex\Ave\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Monstrex\Ave\Contracts\Authorizable;

/**
 * Base Resource class for Ave v2
 */
abstract class Resource implements Authorizable
{
    /** @var class-string<Model>|null */
    public static ?string $model = null;

    /** @var class-string|null Policy class for authorization */
    public static ?string $policy = null;

    public static ?string $label = null;
    public static ?string $singularLabel = null;
    public static ?string $icon = null;
    public static ?string $slug = null;

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
     *
     * @param string $ability Action name (viewAny, view, create, update, delete)
     * @param Authenticatable|null $user Current user
     * @param mixed $model Model instance for singular checks (view, update, delete)
     * @return bool
     */
    public function can(string $ability, ?Authenticatable $user, mixed $model = null): bool
    {
        // Check if resource has a policy configured
        if (!static::$policy) {
            return true; // No policy = allowed by default
        }

        // If no user, deny access
        if (!$user) {
            return false;
        }

        // Use Laravel Gate to check policy
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

    /**
     * Get resource singular label
     */
    public static function getSingularLabel(): string
    {
        return static::$singularLabel ?? static::getLabel();
    }

    /**
     * Get resource icon identifier.
     */
    public static function getIcon(): ?string
    {
        return static::$icon;
    }


    /**
     * Get resource slug (alternative method for controller)
     */
    public function slug(): string
    {
        return static::getSlug();
    }

    /**
     * Get resource label (alternative method for controller)
     */
    public function label(): string
    {
        return static::getLabel();
    }

    /**
     * Get singular label via instance.
     */
    public function singularLabel(): string
    {
        return static::getSingularLabel();
    }

    public function icon(): ?string
    {
        return static::getIcon();
    }

    /**
     * Get a new model instance
     */
    public function getModel()
    {
        return new (static::$model)();
    }

    /**
     * Get a new query for the model
     */
    public function newQuery()
    {
        $query = static::$model::query();
        return static::applyEagerLoading($query);
    }
}

