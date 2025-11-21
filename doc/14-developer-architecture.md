# Developer Architecture

This appendix is aimed at contributors who maintain Ave itself. It summarises how the core is wired together so you can trace requests, extend services, and debug issues confidently.

## High-Level Map

```
Request (/admin/resource/articles)
   → Router (RouteRegistrar)
   → ResourceController + action classes
   → Resource runtime (Table/Form builders, Criteria, Actions)
   → Rendering (Blade views + compiled assets)
   → Persistence (ResourcePersistence)
   → Response (HTML or JSON)
```

## Service Provider (`src/Providers/AveServiceProvider.php`)

- Registers singletons for registries, discovery services, `AccessManager`, breadcrumbs, renderer, validator, persistence, `MediaRepository`, etc.
- Merges configuration from `config/ave.php` and exposes helper functions (`ave_route_prefix()`, `ave_auth_guard()`, …).
- Publishes config, migrations, views, translations, stubs, and assets under the `ave-*` tags.
- Boot pipeline:
  - Load migrations, views, translations.
  - Attach `SidebarComposer` to `ave::partials.sidebar`.
  - Register Gate integration via `Gate::before()` to proxy ACL checks.
  - Register HTTP routes.
  - Register artisan commands (`ave:install`, `ave:resource`).
  - Discover resources/pages and register them with `ResourceManager`/`PageManager`.

## Discovery & Registry

- `AdminResourceDiscovery` scans the core resource folders and `app/Ave/Resources` for classes extending `Monstrex\Ave\Core\Resource`.
- `ResourceManager` stores the slug/class map, instantiates resources, and triggers permission registration.
- `AdminPageDiscovery` + `PageManager` provide the same mechanism for standalone admin pages.

## Routing & Controllers

`RouteRegistrar` builds three route groups under the configured prefix:

1. **Guest** — login form & submission with `web`, `guest:<guard>`, and throttle middleware.
2. **API** — utility endpoints (slug generator, etc.) protected by throttles and exception middleware.
3. **Protected** — dashboard, resource CRUD, media endpoints, locale switching, logout; guarded by `auth:<guard>`, locale middleware, and exception handler.

Each CRUD route maps to `Monstrex\Ave\Http\Controllers\ResourceController`. The controller delegates to action classes in `src/Http/Controllers/Resource/Actions/*` (`IndexAction`, `TableJsonAction`, `InlineUpdateAction`, `RunBulkAction`, …), which resolve the resource, apply authorisation, and invoke table/form builders.

Media routes hit `MediaController` (upload, delete, reorder, crop, update props). Locale switching is handled by `LocaleController`.

## Rendering Pipeline

1. `Resource::table()`/`Resource::form()` describe the UI.
2. `CriteriaPipeline` applies search, sort, filters, soft-delete toggles, and custom criteria.
3. `ResourceRenderer` converts the table/form objects into Blade views (`resources/views`) and shares metadata with front-end scripts.
4. Compiled assets (Vite build) live in `dist/` and are published to `public/vendor/ave`.

## Persistence & Lifecycle

- `ResourcePersistence` wraps create/update/delete operations in database transactions, fires events (`ResourceCreating`, `ResourceCreated`, etc.), and runs `Resource` hooks (`beforeCreate`, `afterUpdate`, …).
- Fields can return `FieldPersistenceResult::skip()` to defer relation syncs or media processing until the parent model exists.
- `FormContext` supplies the current record, request metadata, validation errors, and a consistent data source (model or array).

## Access Control

`AccessManager` handles ACL duties:

- Reads `config('ave.acl')` for enabled flag, default abilities, super role slug, cache TTL, fallback-to-default behaviour.
- Registers permissions per resource slug/ability and attaches them to roles flagged as `is_default`.
- Provides `allows()`/`bulkAllows()` helpers used by controllers and menu composers.
- Integrates with Laravel Gate by short-circuiting `Gate::before()`; policies still run afterwards when needed.

## Frontend Components

- Blade components live in `resources/views/components` (`forms/fields/*`, `partials/index/*`, etc.).
- JavaScript is bundled via Vite (`vite.config.js`) and relies on lightweight modules rather than heavy frameworks.
- `vendor:publish --tag=ave-views`/`--tag=ave-assets` let projects override specific templates or rebuild assets.

## Extension Points

- **Custom fields** — extend `Monstrex\Ave\Core\Fields\AbstractField`, implement relevant contracts, and provide a Blade template.
- **Custom columns** — extend `Monstrex\Ave\Core\Columns\Column`.
- **Custom actions** — implement the appropriate action interface (`RowAction`, `BulkAction`, etc.).
- **Custom criteria** — implement `Monstrex\Ave\Core\Criteria\Contracts\Criterion` and return it from `Resource::getCriteria()`.
- **Custom pages** — place page classes under `app/Ave/Pages` so discovery can register them.

Keep application-specific logic in `app/Ave/...` and reserve the core directories for reusable components—this separation keeps upgrades and merges painless.
