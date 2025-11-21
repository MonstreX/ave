# Fields

Fields power every form rendered by Ave. They encapsulate validation, rendering, state paths, and persistence concerns so you can assemble complex forms without manually wiring HTML inputs.

The base implementation lives in `src/Core/Fields/AbstractField.php`. Many fields also implement additional contracts:

- `HandlesFormRequest` – capture data from the current `Request` before validation (Fieldset, Media).
- `HandlesPersistence` – transform posted values, register deferred actions (`FieldPersistenceResult`).
- `ProvidesValidationRules` / `ProvidesValidationAttributes` – expose additional validation metadata.
- `HandlesNestedValue` / `HandlesNestedCleanup` – used when the field can live inside repeaters.

## Field Lifecycle

1. **Construction** — `Field::make('title')` defines the base key. Fields are often created inline inside `Form::schema()`.
2. **Schema preparation** — layout containers call `setContainer()` so nested state paths can be built.
3. **Rendering** — `render(FormContext $context)` loads `old()` input, pulls model values via `FormContext->dataSource()`, resolves the Blade view (`resources/views/vendor/ave/components/forms/fields/*`), and renders HTML.
4. **Validation** — the `FormValidator` collects rules from `->rules()`, `->required()`, and any `ProvidesValidationRules` implementation.
5. **Persistence** — `ResourcePersistence::mergeFormData()` loops through every field. If a field implements `HandlesPersistence` it returns a `FieldPersistenceResult` so the field can:
   - Modify the payload value (e.g. Slug, File).
   - Register deferred closures executed after the model has been saved (Media, BelongsToManySelect, Fieldset nested media clean-up).
   - Skip persistence entirely (`FieldPersistenceResult::skip()`).

## Common API

These helpers exist on every field (`AbstractField`):

| Method | Description |
| --- | --- |
| `label(string $text)` | Overrides the auto-generated label. |
| `help(string $text)` | Adds a muted helper line. |
| `placeholder(string $text)` | Sets HTML placeholder when applicable. |
| `default(mixed $value)` | Default value for create forms. |
| `required(bool $flag = true)` | Adds the `required` HTML state and validation rule. |
| `disabled(bool $flag = true)` | Renders the field as read-only/disabled. |
| `rules(array $rules)` | Appends raw Laravel validation rules. |
| `template(string $view)` | Uses a custom Blade view (after publishing `ave-views`). |
| `displayAs(string $variant)` | Switches between bundled variants (e.g. `Media` gallery/list). |

### State Paths

`HasStatePath` composes deterministic names for nested inputs (e.g. `sections.0.image`). Containers (`Row`, `Col`, `Tabs`, `Fieldset`) call `getChildStatePath()` to calculate child prefixes. `FormInputName` then converts dot notation into bracket notation for HTML names. This design keeps nested validation errors predictable and is highlighted heavily in `Fieldset`.

### Form Context

Fields can access the current record, model, and request via `FormContext`:

```php
public function prepareForDisplay(FormContext $context): void
{
    $record = $context->record();
    $meta = $context->getMeta('mode'); // create|edit
    $this->setValue($record?->author_email);
}
```

`FormContext` also exposes `registerDeferredAction()` which is how Fieldset and Media clean up nested media collections after a Fieldset item has been deleted.

## Built-in Field Types

| Field | Class | Highlights |
| --- | --- | --- |
| Text input | `TextInput` | Email/url/tel/number modifiers, prefix/suffix, min/max length, pattern validation. |
| Textarea | `Textarea` | Adjustable rows, auto-grow, max length. |
| Number | `Number` | Min/max/step, localization-friendly formatting. |
| Hidden | `Hidden` | Stores hidden metadata or tokens. |
| Password | `PasswordInput` | Optional visibility toggle, min length. |
| Slug | `Slug` | Generates sanitised slug from another field, custom separator. |
| Toggle / Checkbox | `Toggle`, `Checkbox` | Boolean flags, inline labels, supports inline table editing through `BooleanColumn::inlineToggle()`. |
| CheckboxGroup / RadioGroup | `CheckboxGroup`, `RadioGroup` | Option arrays, inline layout, validation for required selections. |
| Select | `Select` | Static option list with nullable/searchable support. |
| Tags | `Tags` | Token-style string arrays (stored as JSON). |
| Color picker | `ColorPicker` | Hex/RGB input with UI picker. |
| Date/time | `DateTimePicker` | Date + time selection, min/max constraints, timezone safe. |
| File | `File` | Upload to configured storage, MIME + size filters, filename/path strategies. |
| Fieldset | `Fieldset` | Repeatable groups stored as JSON (see [Complex Fields](13-complex-fields.md)). |
| Media | `Media` | Drag-and-drop media collections with presets (see [Complex Fields](13-complex-fields.md)). |
| Rich editor | `RichEditor` | Jodit-based editor with toolbar presets (minimal/basic/full). |
| Code editor | `CodeEditor` | Monaco-style editor, language + theme presets, auto-height. |
| BelongsToSelect | `BelongsToSelect` | Dropdown sourced from a `BelongsTo` relation, supports hierarchical trees and custom query modifiers. |
| BelongsToManySelect | `BelongsToManySelect` | Multi-select for pivot relations, syncs via deferred action after save. |

Additional helper classes live in `src/Core/Fields/Presets/*`. For example `RichEditor\MinimalPreset` or `Media\GalleryPreset` configure sensible defaults for common scenarios.

## Validation & Error Handling

- `->rules()` and `->required()` feed into Laravel’s validator.
- Some fields contribute extra rules: e.g. `Fieldset` enforces `array` plus `minItems`/`maxItems`, `Media` adds `nullable` + file rules, `BelongsToSelect` automatically adds `nullable` when `->nullable()` is called.
- Errors are stored in `FormContext->errors()`; each field automatically checks all key permutations (dot notation, bracket notation, raw key) when displaying error messages.

## Customising Rendering

1. Publish the views (`php artisan vendor:publish --tag=ave-views`).
2. Override individual field templates under `resources/views/vendor/ave/components/forms/fields/<field>/<variant>.blade.php`.
3. Optionally point a field to a different template via `->template('vendor.package.custom-field')`.

This approach keeps upgrades safe: you only override the view layer while field logic stays in the package.

## Field Design Tips

- Mix field types so editors instantly understand the hierarchy: visuals (Media, Fieldset) draw attention, while TextInput/Textarea capture quick edits.
- Prefer the relationship-aware fields (`BelongsToSelect`, `BelongsToManySelect`) over generic `Select`. They add eager loading, validation, and sync logic for free.
- Use `help()` to describe business rules (“Changing the slug breaks public URLs”) and `placeholder()` for data format hints.
- When one field depends on another (slug from title, SKU from category), rely on the provided helpers or lifecycle hooks—avoid ad‑hoc JavaScript.
- Fieldset works best for lightweight repeaters. If every item contains its own workflows or approvals, consider promoting it to a dedicated child resource instead.

Study the bundled resources (Roles, Permissions, Menu, etc.) for compact, real-world configurations of these APIs.
