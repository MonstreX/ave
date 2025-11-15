<?php

namespace Monstrex\Ave\Core;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Monstrex\Ave\Admin\Access\AccessManager;
use Monstrex\Ave\Core\Actions\Contracts\ActionInterface;
use Monstrex\Ave\Core\Actions\Contracts\RowAction as RowActionContract;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction as BulkActionContract;
use Monstrex\Ave\Core\Actions\Contracts\FormAction as FormActionContract;
use Monstrex\Ave\Core\Actions\Contracts\GlobalAction as GlobalActionContract;
use Monstrex\Ave\Contracts\Authorizable;
use Monstrex\Ave\Core\Actions\Form\CancelFormAction;
use Monstrex\Ave\Core\Actions\Form\SaveAndContinueFormAction;
use Monstrex\Ave\Core\Actions\Form\SaveFormAction;

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
    public static array $searchable = [];
    public static array $sortable = [];
    public static array $relationMap = [];

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
     * Override to configure custom criteria.
     *
     * @return array<int,\Monstrex\Ave\Core\Criteria\Contracts\Criterion>
     */
    public static function getCriteria(): array
    {
        return [];
    }

    /**
     * Hook: Modify data before creating a new record.
     * Override this method to auto-fill fields, apply transformations, etc.
     *
     * @param array $data Validated form data
     * @param \Illuminate\Http\Request $request Current request
     * @return array Modified data
     */
    public static function beforeCreate(array $data, \Illuminate\Http\Request $request): array
    {
        return $data;
    }

    /**
     * Hook: Modify data before updating an existing record.
     * Override this method to apply transformations, auto-fill fields, etc.
     *
     * @param Model $model Model being updated
     * @param array $data Validated form data
     * @param \Illuminate\Http\Request $request Current request
     * @return array Modified data
     */
    public static function beforeUpdate(Model $model, array $data, \Illuminate\Http\Request $request): array
    {
        return $data;
    }

    /**
     * Hook: Called after a new record has been created and saved.
     * Override this method to perform actions after creation (logging, notifications, etc.)
     *
     * @param Model $model Newly created model
     * @param \Illuminate\Http\Request $request Current request
     * @return void
     */
    public static function afterCreate(Model $model, \Illuminate\Http\Request $request): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook: Called after a record has been updated.
     * Override this method to perform actions after update (logging, cache clearing, etc.)
     *
     * @param Model $model Updated model
     * @param \Illuminate\Http\Request $request Current request
     * @return void
     */
    public static function afterUpdate(Model $model, \Illuminate\Http\Request $request): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook: Called after a record has been deleted.
     * Override this method to perform cleanup actions (logging, file deletion, etc.)
     *
     * @param Model $model Deleted model (with original attributes still accessible)
     * @param \Illuminate\Http\Request $request Current request
     * @return void
     */
    public static function afterDelete(Model $model, \Illuminate\Http\Request $request): void
    {
        // Override in subclass if needed
    }

    /**
     * Hook: Provide custom query parameters for index redirect after save.
     * Override this method to add filters, context params, etc. to redirect URL.
     *
     * @param Model $model Saved model
     * @param \Illuminate\Http\Request $request Current request
     * @param string $mode 'create' or 'edit'
     * @return array Query parameters to add to redirect
     */
    public static function getIndexRedirectParams(Model $model, \Illuminate\Http\Request $request, string $mode): array
    {
        return [];
    }

    /**
     * Define available custom actions for the resource.
     *
     * @return array<int,class-string<ActionInterface>|ActionInterface>
     */
    public static function actions(): array
    {
        return [];
    }

    /**
     * @return array<int,ActionInterface>
     */
    public static function rowActions(): array
    {
        return static::collectActions(static::defaultRowActions(), RowActionContract::class);
    }

    /**
     * @return array<int,ActionInterface>
     */
    public static function bulkActions(): array
    {
        return static::collectActions(static::defaultBulkActions(), BulkActionContract::class);
    }

    /**
     * @return array<int,ActionInterface>
     */
    public static function globalActions(): array
    {
        return static::collectActions(static::defaultGlobalActions(), GlobalActionContract::class);
    }

    /**
     * @return array<int,ActionInterface>
     */
    public static function formActions(): array
    {
        return static::collectActions(static::defaultFormActions(), FormActionContract::class);
    }

    public static function findAction(string $key, string $interface): ?ActionInterface
    {
        $actions = match ($interface) {
            RowActionContract::class => static::rowActions(),
            BulkActionContract::class => static::bulkActions(),
            GlobalActionContract::class => static::globalActions(),
            FormActionContract::class => static::formActions(),
            default => [],
        };

        foreach ($actions as $action) {
            if ($action->key() === $key) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Default row actions available for every resource.
     *
     * @return array<int,class-string<ActionInterface>>
     */
    protected static function defaultRowActions(): array
    {
        return [
            \Monstrex\Ave\Core\Actions\EditAction::class,
            \Monstrex\Ave\Core\Actions\DeleteAction::class,
        ];
    }

    /**
     * Default bulk actions.
     *
     * @return array<int,class-string<ActionInterface>>
     */
    protected static function defaultBulkActions(): array
    {
        return [
            \Monstrex\Ave\Core\Actions\DeleteAction::class,
        ];
    }

    /**
     * Default global actions.
     *
     * @return array<int,class-string<ActionInterface>>
     */
    protected static function defaultGlobalActions(): array
    {
        return [];
    }

    protected static array $defaultFormActionClasses = [
        SaveFormAction::class,
        SaveAndContinueFormAction::class,
        CancelFormAction::class,
    ];

    protected static array $disabledDefaultFormActionClasses = [];

    /**
     * Default form actions.
     *
     * @return array<int,class-string<ActionInterface>>
     */
    protected static function defaultFormActions(): array
    {
        return array_values(array_filter(
            static::$defaultFormActionClasses,
            fn ($class) => !in_array($class, static::$disabledDefaultFormActionClasses, true)
        ));
    }

    /**
     * Collect default + custom actions for the given interface.
     *
     * @param array<int,class-string<ActionInterface>> $defaults
     * @param class-string $interface
     * @return array<int,ActionInterface>
     */
    protected static function collectActions(array $defaults, string $interface): array
    {
        $instances = [];

        foreach ($defaults as $class) {
            $instance = static::instantiateAction($class);
            if ($instance) {
                $instances[] = $instance;
            }
        }

        foreach (static::actions() as $action) {
            $instance = static::instantiateAction($action);
            if ($instance) {
                $instances[] = $instance;
            }
        }

        $filtered = [];

        foreach ($instances as $instance) {
            if (!($instance instanceof $interface)) {
                continue;
            }

            $filtered[$instance->key()] = $instance;
        }

        $result = array_values($filtered);

        // Sort actions by order (lower = first)
        usort($result, fn($a, $b) => $a->order() <=> $b->order());

        return $result;
    }

    protected static function instantiateAction(mixed $action): ?ActionInterface
    {
        if ($action instanceof ActionInterface) {
            return $action;
        }

        if (is_string($action) && class_exists($action)) {
            if (function_exists('app')) {
                $resolved = app()->make($action);
            } else {
                $resolved = new $action();
            }

            return $resolved instanceof ActionInterface ? $resolved : null;
        }

        return null;
    }

    public static function searchableColumns(Table $table): array
    {
        if (!empty(static::$searchable)) {
            return static::$searchable;
        }

        return array_values(array_map(
            fn ($column) => $column->key(),
            array_filter($table->getColumns(), fn ($column) => method_exists($column, 'isSearchable') && $column->isSearchable()),
        ));
    }

    public static function sortableColumns(Table $table): array
    {
        if (!empty(static::$sortable)) {
            return static::$sortable;
        }

        return array_values(array_map(
            fn ($column) => $column->key(),
            array_filter($table->getColumns(), fn ($column) => method_exists($column, 'isSortable') && $column->isSortable()),
        ));
    }

    public static function relationMap(): array
    {
        return static::$relationMap;
    }

    public static function usesSoftDeletes(): bool
    {
        if (!static::$model) {
            return false;
        }

        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(static::$model),
            true
        );
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
        $accessManager = app()->bound(AccessManager::class)
            ? app(AccessManager::class)
            : null;

        if ($accessManager && $accessManager->isEnabled()) {
            return $accessManager->allows($user, static::getSlug(), $ability);
        }

        if (!$user) {
            return false;
        }

        if (!static::$policy) {
            return false;
        }

        return Gate::forUser($user)->allows($ability, $model ?? static::$model);
    }

    /**
     * @return array<int|string,string|array<string,mixed>>
     */
    public static function permissionAbilities(): array
    {
        return config('ave.acl.default_abilities', [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
        ]);
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
