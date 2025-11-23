# Layouts & Form Composition

Ave uses layout components to arrange fields inside forms. Every layout component implements `Monstrex\Ave\Core\Components\FormComponent` and mixes in `HasComponents`, so you can nest them arbitrarily.

## Containers & State Paths

Layout components implement `getChildStatePath()` which lets nested fields inherit a hierarchical state path (e.g. `tabs.basic.title`). This matters for validation errors and Fieldset/Media nested keys. You almost never have to call these methods manually; simply pass fields into the `schema()` of a component.

## Component Reference

### Div

`Div::make()->schema([...])` is the simplest container. Forms automatically wrap “loose” fields in a `Div`, so you only need this when you want to add CSS classes or conditional rendering.

```php
Div::make()->classes('grid grid-cols-2 gap-4')->schema([
    TextInput::make('first_name'),
    TextInput::make('last_name'),
]);
```

### Row & Col

Responsive grid system (12 columns by default).

```php
Row::make()->schema([
    Col::make(8)->schema([
        TextInput::make('title')->required(),
        Slug::make('slug')->from('title'),
    ]),
    Col::make(4)->schema([
        Toggle::make('status')->label('Published')->default(true),
        DateTimePicker::make('published_at'),
    ]),
]);
```

- `Col::make(int $span = 12)` – width in grid columns.
- `Col::grow()`/`shrink()` helpers (see class definition) control flex behaviour.

### Panel

Card-like container with optional header/footer text.

```php
Panel::make('SEO')
    ->schema([
        TextInput::make('meta_title'),
        Textarea::make('meta_description')->rows(3),
    ])
    ->footer('Used for Open Graph & search snippets');
```

Panels are useful to group related Fieldsets or Media sections. You can also pass closures to `header()`/`footer()` for dynamic content.

### Tabs & Tab

Organise large forms into multiple tabs. Tabs render nav pills across the top and keep their content mounted so state is preserved while switching.

```php
Tabs::make()->schema([
    Tab::make('Basic')->schema([...]),
    Tab::make('Content')->schema([...]),
    Tab::make('SEO')->schema([...]),
])->activeTab(1); // 1-based index
```

Tab components accept icons, badges, disabled states, and lazy-loading flags when the view needs to defer heavy content.

### Group

Lightweight wrapper for applying shared classes/attributes to a set of fields.

```php
Group::make()
    ->classes('border border-dashed p-4 rounded')
    ->schema([
        TextInput::make('street'),
        TextInput::make('city'),
        TextInput::make('postcode'),
    ]);
```

Groups are sometimes used inside Fieldset items to visually separate sections.

## Sticky Form Actions

Sticky action bar теперь активна по умолчанию и фиксирует кнопки внизу экрана. В редких случаях, когда нужна классическая статичная панель, вызовите `stickyActions(false)` (или `disableStickyActions()`) на форме.

```php
Form::make()
    ->stickyActions(false)
    ->schema([...]);
```

`CancelFormAction` respects `Form::cancelUrl()` when provided.

## Layout Strategies in Live Resources

- **Article Resource** — uses `Tabs` with nested `Row`/`Col` grids inside each tab, grouping related fields (basic info, content, status, resources).
- **Ave Site Page Resource** — uses Tabs for “Main”, “Media”, “SEO”, “Options”, “Additional”, with Fieldsets and Media inside columns.
- **Category Resource** — demonstrates a single-column form composed of stacked Rows and Fieldsets.

Studying those files reveals best practices for balancing readability (one component per logical section) with UX (collapsing rarely-used options into panels or tabs).

## Tips

1. **Keep sections short** — rather than one massive tab filled with dozens of fields, break them into Panels or Tabs.
2. **Use Col widths consistently** — e.g. `Col::make(6)` for half-width inputs across the form to maintain rhythm.
3. **Combine layout + validation** — layout components don’t affect validation, so you can move fields around freely without touching model rules.
4. **Conditional sections** — wrap optional inputs inside `Group`/`Panel` components and hide them via Blade when necessary (publish views to customise).
