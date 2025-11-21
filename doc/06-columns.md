# Columns

Columns define how records appear inside resource tables. Every column extends `Monstrex\Ave\Core\Columns\Column` and can participate in search, sorting, inline editing, hyperlinks, and custom Blade rendering.

## Shared API (`Column`)

| Method | Description |
| --- | --- |
| `make('title')` | Static constructor; key can reference nested relations (`category.title`). |
| `label('Title')` | Override header text. |
| `sortable()` / `searchable()` | Opt-in to sorting/searching. Table builders automatically detect available columns, but you can override the defaults via `Resource::$sortable/$searchable`. |
| `hidden()` | Hide the column by default (useful for actions). |
| `align('center')`, `width(120)`, `minWidth('150px')`, `wrap()` | Layout helpers. |
| `cellClass('text-center')`, `headerClass('px-3')`, `tooltip('...')`, `helpText('...')` | Additional UI controls. |
| `fontSize('0.875rem')`, `fontWeight(600)`, `italic()`, `textColor('#777')` | Typography overrides. |
| `format(fn ($value, $record) => ...)` | Mutate the displayed value without touching the raw data. |
| `view('vendor.package.column')` | Render using a custom Blade template (see `TemplateColumn`). |
| `linkAction('edit', ['slug' => ...])` / `linkUrl('https://...')` | Wrap the value inside a link to either another Ave action or a custom URL (closure allowed). |
| `inline('text', ['field' => 'title'])` | Enable inline editing (see below). |

Inline validation rules are specified via `inlineRules('required|string|max:255')`. `BooleanColumn::inlineToggle()` is a convenience method that turns the column into a click-to-toggle switch.

## Column Types

### TextColumn

The default column type. Extra helpers:

- `limit(int $characters)` – truncates text with ellipsis.
- `uppercase()`, `suffix(' USD')`, `linkToEdit()` convenience methods.

### BooleanColumn

Shows boolean state with icons/badges and supports inline toggles.

```php
BooleanColumn::make('status')
    ->label('Published')
    ->trueLabel('Published')->falseLabel('Draft')
    ->trueIcon('voyager-check')->falseIcon('voyager-x')
    ->trueColor('success')->falseColor('danger')
    ->inlineToggle(); // uses AJAX endpoint /resource/{slug}/{id}/inline
```

### BadgeColumn

Displays coloured pills.

```php
BadgeColumn::make('category.title')
    ->label('Category')
    ->colors(['News' => 'primary', 'Review' => 'success'])
    ->icons(['News' => 'voyager-news'])
    ->pill();
```

### ImageColumn

Renders images from attributes or media collections.

```php
ImageColumn::make('main_image')
    ->label('Preview')
    ->fromMedia('main', 'thumb') // collection + conversion
    ->shape('rounded')           // rounded|circle|square
    ->height(40)
    ->fit('cover');
```

### TemplateColumn

Use a Blade snippet when you need complete control:

```php
TemplateColumn::make('actions')
    ->label('Actions')
    ->view('ave::columns.actions.custom');
```

The template receives `$record`, `$column`, and `$value`.

### ComputedColumn

Wraps a callback to compute values outside the model attributes.

```php
ComputedColumn::make('price_with_tax')
    ->label('Price (VAT)')
    ->format(fn ($value, $record) => money_format('%.2n', $record->price * 1.2));
```

## Inline Editing

`Table::findInlineColumn()` looks for columns configured via `inline()`. When an inline edit request hits `PATCH /admin/resource/{slug}/{id}/inline`, the controller:

1. Validates the payload using `inlineValidationRules()`.
2. Updates the model and broadcasts the formatted value back as JSON (`InlineUpdateAction`).

Boolean columns can be toggled without specifying a value; the endpoint flips between `trueValue()` and `falseValue()`.

## Display Modes & Columns

- **Tree view** (`Table::tree()`) — the first column usually contains the indentation. Use `TextColumn::make('title')->bold()->linkAction('edit')` to emphasise hierarchy.
- **Sortable list** — when `Table::sortable()` or `groupedSortable()` is active, include narrow columns (ID, drag handle) to keep rows compact.
- **Inline media** — combine `ImageColumn` with `TextColumn::suffix()` for galleries or product listings.

## Examples in Live Resources

- **Article table** (`app/Ave/Resources/Article/Resource.php`) uses text, badge, boolean, and image columns with inline toggles and custom formatters.
- **Ave Site Page resource** uses `Column` (base class) but demonstrates tree view with status toggles and relation columns.

Studying these files shows how columns interact with `Table` options like `->defaultSort()`, `->filters()`, and display modes.
