# Permissions & ACL

Ave layers a database-driven ACL on top of Laravel’s Gate/Policy system. Policies continue to work, but `AccessManager` becomes the first line of defence thanks to the `Gate::before()` hook registered by `AveServiceProvider`.

## Configuration (`config/ave.php`)

```php
'acl' => [
    'enabled' => env('AVE_ACL_ENABLED', true),
    'default_abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
    'super_role' => 'admin',                // role slug that bypasses checks
    'cache_ttl' => 300,                     // seconds to cache role→permission maps
    'fallback_to_default_roles' => true,    // inherit from roles marked is_default
],
```

Set `enabled` to `false` if you want to rely solely on policies. `super_role` lets you keep a single “God” role without assigning every permission manually.

## Permission Model

Migrations published by `ave:install` create:

- `ave_roles` — role metadata (`slug`, `name`, `is_default`).
- `ave_permissions` — resource slug + ability pairs.
- `ave_permission_role` — pivot between roles and permissions.
- `ave_role_user` — user assignments.

`AccessManager` uses Eloquent models under `Monstrex\Ave\Models\Role` and `Monstrex\Ave\Models\Permission`.

## Registration Flow

1. When `ResourceManager` registers a resource, it calls `AccessManager::registerPermissions($slug, $abilities)`.
2. `AccessManager` creates missing permission rows and automatically attaches them to roles marked as `is_default`.
3. Your seeders should create the initial roles (e.g., Admin, Editor) and mark default roles when appropriate.

Override `Resource::permissionAbilities()` to add custom actions:

```php
public static function permissionAbilities(): array
{
    return [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'publish' => ['name' => 'Publish Articles'],
    ];
}
```

Each entry can be a string (ability name) or an array with extra metadata (display name, description). When AccessManager inserts new permissions it uses these values.

## Runtime Checks

- `Gate::allows('posts.update')` or `$user->can('update', $post)` hits `AccessManager::allows($user, 'posts', 'update')`.
- Row/bulk/global actions define `$ability` on the action class. Buttons disappear automatically if the ability is denied.
- Menu items store `resource_slug` + `ability`. `SidebarComposer` preloads all relevant permissions with `AccessManager::bulkAllows()` so you don’t run dozens of queries.
- `Resource::can($ability, $user, $model = null)` defers to Gate, so you can call `$resource->can('create', auth()->user())` from controllers or views.

When a permission is missing or tables haven’t been migrated yet, AccessManager logs critical messages to help you diagnose the issue.

## Policies

Policies still participate after AccessManager:

1. `Gate::before()` checks ACL (super role, explicit permission, fallback roles).
2. If ACL denies the action, Gate tries the registered policy (if any).
3. The final decision feeds back into controllers, Blade directives, etc.

Use policies for record-level constraints (“only allow editing own records”) and ACL for coarse-grained abilities (“edit articles at all”).

## Seeding Roles & Permissions

Typical bootstrap seeder:

```php
use Monstrex\Ave\Models\Role;

$admin = Role::firstOrCreate(
    ['slug' => 'admin'],
    ['name' => 'Administrators', 'is_default' => false]
);

$editor = Role::firstOrCreate(
    ['slug' => 'editor'],
    ['name' => 'Editors', 'is_default' => false]
);

// After resources are registered, attach permissions by slug.
$permissions = \Monstrex\Ave\Models\Permission::query()
    ->whereIn('resource_slug', ['articles', 'categories'])
    ->pluck('id', 'resource_slug');

$admin->permissions()->syncWithoutDetaching($permissions->values());
$editor->permissions()->syncWithoutDetaching($permissions->only(['articles']));
```

Assign roles to users via the `ave_role_user` pivot table or the provided relationships on the `Role` model.

## Best Practices

- **Run migrations early** — missing ACL tables block access. `AccessManager::tablesExist()` logs critical errors when a table is missing.
- **Invalidate caches when changing roles** — roles/permissions are cached per user for `cache_ttl` seconds. Flush the cache when you bulk-edit roles.
- **Use fallback roles strategically** — `fallback_to_default_roles` is handy for small teams but consider disabling it in stricter environments (users must be explicitly assigned to roles).
- **Keep ability names stable** — they act as permission keys, route names (`ave.resource.action`), and Gate ability strings.
