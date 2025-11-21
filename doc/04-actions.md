# Actions

Actions encapsulate side effects triggered from the resource table or form footer. They are small objects that implement one or more action interfaces:

- `RowAction` — operates on a single record (table row buttons).
- `BulkAction` — operates on a selection of records (multi-select toolbar).
- `GlobalAction` — operates at the resource level (no selection required).
- `FormAction` — appears next to the form submit button (Save, Save & continue, Cancel).
- `FormButtonAction` — specialised actions that return a redirect or JSON response from within a form modal.

All actions extend `Monstrex\Ave\Core\Actions\BaseAction` and implement `ActionInterface`. Runtime helpers live in `src/Core/Actions/*`.

## Anatomy of an Action

```php
use Monstrex\Ave\Core\Actions\BaseAction;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

class ApproveArticleAction extends BaseAction implements RowAction
{
    protected string $color = 'success';
    protected ?string $ability = 'update';

    public function label(): string
    {
        return 'Approve';
    }

    public function confirm(): ?string
    {
        return 'Mark this article as approved?';
    }

    public function handle(ActionContext $context, \Illuminate\Http\Request $request): mixed
    {
        $model = $context->model();
        $model->forceFill(['is_approved' => true, 'status' => true])->save();

        return ['message' => 'Article approved'];
    }
}
```

Key helpers provided by `BaseAction`:

| Method | Purpose |
| --- | --- |
| `key()` | Unique key (defaults to snake_case class name). |
| `label()` | Button text. |
| `icon()` | Icon slug (Voyager icon set). |
| `color()` | Button colour: `default`, `primary`, `success`, `warning`, `danger`. |
| `confirm()` | Confirmation message. Returning `null` skips the dialog. |
| `ability()` | Gate ability that must be authorised (defaults to `null`, meaning use the action interface defaults). |
| `form()` | Optional form schema (array of fields) rendered before `handle()` runs. |
| `rules()` | Validation rules applied to the action form. |
| `handle(ActionContext $context, Request $request)` | Execute the action. Return values are serialised to JSON and shown as toast messages when applicable. |

`ActionContext` (see `src/Core/Actions/Support/ActionContext.php`) exposes:

- `resourceClass()` — current resource class name.
- `model()` / `models()` — the record(s) involved.
- `ids()` — selected IDs for bulk actions.
- `request()` — current request instance.

## Registering Actions

Override `public static function actions(): array` in the resource and return action class names or instantiated objects. The base `Resource` class merges your list with defaults for each action type:

- **Row** — `EditAction`, `DeleteAction`.
- **Bulk** — `DeleteAction`.
- **Global** — none by default.
- **Form** — `SaveFormAction`, `SaveAndContinueFormAction`, `CancelFormAction`.

Actions are filtered by interface before rendering; a single class can implement multiple interfaces if needed. Ordering is controlled via `public function order(): int` (lower comes first). Duplicate keys are de-duplicated automatically.

```php
public static function actions(): array
{
    return [
        \App\Ave\Resources\Article\Actions\ApproveArticleAction::class,
        \App\Ave\Resources\Article\Actions\FeatureArticlesAction::class, // Bulk action
    ];
}
```

## Built-in Actions

| Class | Interface | Behaviour |
| --- | --- | --- |
| `EditAction` | Row | Redirects to the resource edit screen. |
| `DeleteAction` | Row + Bulk | Soft-deletes or force-deletes the records depending on model traits. |
| `RestoreAction` | Row + Bulk | Restores soft-deleted records when the model uses `SoftDeletes`. |
| `CreateInModalAction` | Global | Opens a modal with the resource form in “create” mode. |
| `EditInModalAction` | Row | Opens the edit form inside a modal. |
| `SaveFormAction` | Form | Default “Save” button. |
| `SaveAndContinueFormAction` | Form | Saves and redirects back to edit the same record. |
| `CancelFormAction` | Form | Redirects back to the index route. |

These live under `src/Core/Actions`. Use them as references when creating your own subclasses or when you want to understand the expected return payload.

## Modal Forms in Actions

Any action can expose a form by returning an array of field instances from `form()`. The fields follow the same contracts as regular resource forms. `ActionContext` provides the current record so you can pre-fill defaults. Validate input via `rules()` or by calling `$request->validate()` manually.

Example bulk action from `app/Ave/Resources/Article/Actions/FeatureArticlesAction.php`:

```php
class FeatureArticlesAction extends BaseAction implements BulkAction
{
    protected string $color = 'warning';
    protected ?string $ability = 'update';

    public function label(): string
    {
        return 'Mark as Featured';
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        $ids = $context->ids();
        $resourceClass = $context->resourceClass();
        $modelClass = $resourceClass::$model;
        $modelClass::whereIn((new $modelClass)->getKeyName(), $ids)->update(['featured' => true]);

        return ['message' => 'Marked '.count($ids).' article(s) as featured'];
    }
}
```

## Permissions & Visibility

- Every action checks `ability()` (or a default ability derived from the route) against Laravel Gate. Gate is wired to `AccessManager`, so ACL rules enforce themselves.
- Use `visible(ActionContext $context): bool` or `authorized(ActionContext $context): bool` overrides to hide actions dynamically.
- You can also check `ActionContext::user()` inside `handle()` to guard domain-specific cases.

## Form Actions vs. Table Actions

Form actions (`FormAction` interface) operate inside the form submission lifecycle. They receive both the request and the current record (if editing) and can return:

- `null` — the default `Save` flow continues.
- `['redirect' => route(...)]` — redirect somewhere specific.
- `['message' => 'Saved']` — just show a toast message and stay on the page/modal.

Use them for custom buttons such as “Save & Publish” or “Approve & Close” without duplicating controller logic.

## Testing Actions

Actions are simple classes: instantiate them in unit tests, pass a fake `ActionContext`, and call `handle()`. Integration tests can hit the HTTP endpoints under:

- `POST /admin/resource/{slug}/{id}/actions/{action}` — row.
- `POST /admin/resource/{slug}/actions/{action}/bulk` — bulk.
- `POST /admin/resource/{slug}/actions/{action}/global` — global.
- `POST /admin/resource/{slug}/{id?}/actions/{action}/form` — form action buttons.

All routes are registered in `RouteRegistrar::registerResourceCrudRoutes()`.
