# Resource Lifecycle & Persistence

Resource lifecycle hooks and the persistence service let you plug into CRUD operations without overriding controllers. This chapter describes the flow from HTTP request to database transaction and how to leverage `FormContext`, deferred actions, and events.

## Request → Persistence Flow

1. **Controller resolves resource** via slug and authorises the action.
2. **FormValidator** validates the incoming request using field rules.
3. **ResourcePersistence** starts a transaction and emits events (`ResourceCreating`, `ResourceUpdating`, etc.).
4. **Hooks** (`beforeCreate`, `beforeUpdate`) can mutate the validated payload.
5. **Fields** contribute payload data via `FieldPersistenceResult` and register deferred actions (e.g., attach media after save).
6. **Model saved** using `Model::create()` or `$model->update()`.
7. **Deferred actions run** inside the same transaction.
8. **Hooks** (`afterCreate`, `afterUpdate`, `afterDelete`) and events fire.
9. **Redirect** happens, optionally augmented by `getIndexRedirectParams()`.

Delete operations follow a similar pattern: `ResourceDeleting` event, `$model->delete()`, then `ResourceDeleted`.

## Hook Reference

Hooks live on `Monstrex\Ave\Core\Resource` as static methods.

| Hook | When it runs | Return value |
| --- | --- | --- |
| `beforeCreate(array $data, Request $request): array` | After validation, before `Model::create()` | Mutated payload |
| `beforeUpdate(Model $model, array $data, Request $request): array` | Before `$model->update()` | Mutated payload |
| `afterCreate(Model $model, Request $request): void` | After model + deferred actions | — |
| `afterUpdate(Model $model, Request $request): void` | After update + deferred actions | — |
| `afterDelete(Model $model, Request $request): void` | After delete (soft or hard) | — |
| `getIndexRedirectParams(Model $model, Request $request, string $mode): array` | Before redirecting to `ave.resource.index` | Query parameters to append |

Override only the hooks you need. Each hook receives the current HTTP request so you can inspect route parameters, logged-in user, etc.

## Syncing Relationships

`ResourcePersistence::syncRelations()` calls a static `syncRelations(Model $model, array $data, Request $request)` method on your resource if it exists. Use this when you need explicit control over has-many relationships that aren’t covered by existing fields.

```php
public static function syncRelations(Model $model, array $data, Request $request): void
{
    if (isset($data['tag_ids'])) {
        $model->tags()->sync($data['tag_ids']);
    }
}
```

For BelongsToMany fields use `BelongsToManySelect`; it already syncs via deferred actions and does not require overriding `syncRelations()`.

## FormContext & Deferred Actions

`FormContext` tracks:

- Mode (`create` or `edit`), current record, and request metadata.
- Old input and validation errors (for re-rendering forms).
- Registered deferred actions (closures executed after the model is saved).

Fields that implement `HandlesPersistence` return a `FieldPersistenceResult`. Example from `Media::prepareForSave()`:

```php
return FieldPersistenceResult::skip([
    function (Model $record) use ($value, $collection) {
        $this->mediaRepository()->attach($record, $collection, $value['uploaded_ids']);
    },
]);
```

Deferred actions run after `create()`/`update()` but before the transaction commits, guaranteeing consistency.

## Soft Deletes & Queries

- `Resource::usesSoftDeletes()` checks whether the model uses the `SoftDeletes` trait.
- `Resource::newQuery()` automatically applies `$with`/`$withCount` eager loading you configure on the resource.
- The soft-delete criterion adds `?trashed=with|only` support on index pages.

## Events

Located under `src/Events/*`:

- `ResourceCreating`, `ResourceCreated`
- `ResourceUpdating`, `ResourceUpdated`
- `ResourceDeleting`, `ResourceDeleted`

Subscribe to these events when you need to trigger global behaviour (audit logs, cache busting) without touching hooks.

## Redirects & Flashing State

`ResourceController` redirects back to `ave.resource.index` with flash messages. Override `getIndexRedirectParams()` to keep filters active or highlight the edited record:

```php
public static function getIndexRedirectParams(Model $model, Request $request, string $mode): array
{
    return [
        'status' => $model->status,
        'highlight' => $model->getKey(),
    ];
}
```

Use this in combination with table filters to return users to the list they were working with.

## Examples

- **Article Resource** — `beforeCreate` sets `author_id`, `afterUpdate` clears caches, `actions()` hook ties into ACL.
- **Ave Site Form Resource** — uses `syncRelations()` to manage form fields stored as JSON and to trigger notifications.

Reading these resources side by side shows how hooks, deferred actions, and FormContext combine to handle complex scenarios without overriding controllers.
