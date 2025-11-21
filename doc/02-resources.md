# Resources

Every screen in the admin panel is powered by a resource class. A resource connects an Eloquent model to:

- List/table presentation
- Create/edit form schema
- Available actions and permissions
- Lifecycle hooks (validation, persistence, redirects)

This chapter explains the full anatomy of a resource and the workflow to shape it confidently.

## File Layout & Discovery

Place each resource inside its own folder under `app/Ave/Resources`:

```
app/Ave/Resources/Product/
├── Resource.php          # required
├── Table.php             # optional nearby definition (static define method)
├── Form.php              # optional nearby definition
└── Actions/
    ├── PublishProductAction.php
    └── ...
```

Ave automatically discovers every class that extends `Monstrex\Ave\Core\Resource`. Nearby `Table`/`Form` classes are loaded when the `Resource` method calls `parent::table()`/`parent::form()` or stays empty. Use them when the resource grows large and you want to keep `Resource.php` focused on metadata plus hooks.

## Metadata Checklist

Define these static properties at the top of `Resource.php`:

| Property | Why it matters |
| --- | --- |
| `public static ?string $model` | The Eloquent model backing the resource. **Required**. |
| `public static ?string $label` / `$singularLabel` | Display names in navigation, breadcrumbs, headings. Provide translated strings if necessary. |
| `public static ?string $slug` | URL segment, permission namespace, and Gate ability prefix. Choose something stable (`products`, `site-pages`, etc.). |
| `public static ?string $icon` | Sidebar icon (Voyager icon set by default). |
| `public static ?string $group` / `$navSort` | Optional ordering/grouping hints for your menu composer. |
| `public static array $with` / `$withCount` | Relations or counts to eager load on the index view. Prevents N+1 queries. |
| `public static array $searchable` / `$sortable` | Override automatic detection when you need custom columns for search/sort. |

These values feed the UI automatically: as soon as the resource registers, it appears in permission matrices, sidebar menus, and action labels.

## Table Builder

`public static function table($ctx): Table` configures the listing page. Treat it as the blueprint for administrators to browse and curate records.

### Step-by-step recipe

1. **Start with `Table::make()`** and declare columns in priority order.
2. **Pick the right column type** for each field (see [Columns](06-columns.md)). Don’t stretch `TextColumn` to show images or booleans—use `ImageColumn`, `BooleanColumn`, or `BadgeColumn` so the UX stays consistent.
3. **Wire inline interactions**:  
   - Use `linkAction('edit')` or `linkUrl()` for quick navigation.  
   - Enable `inlineToggle()` on boolean columns that should be flipped directly inside the table.  
   - Add `inline('text', ['field' => 'title'])` when inline editing should open an input.
4. **Add filters** with clear labels and option maps. Always provide human‑readable values for badges: users remember labels, not IDs.
5. **Select a display mode** depending on data shape:  
   - `table()` (default) for flat lists.  
   - `sortable($orderColumn)` for custom ordering.  
   - `tree($parentColumn, $orderColumn, $maxDepth)` for hierarchies.  
   - `groupedSortable($groupColumn, relationName)` for “lists with sections”.
6. **Tune pagination** using `perPage()` and `perPageOptions()`. Use `maxInstantLoad()` + `forcePaginationOnOverflow()` to keep drag-and-drop tables performant.
7. **Expose search & sorting**:  
   - Add `->searchable()` on relevant columns or override `$searchable`.  
   - Set `->defaultSort('column', 'direction')` to keep lists predictable.

### Example block

```php
public static function table($ctx): Table
{
    return Table::make()
        ->columns([
            TextColumn::make('id')->label('#')->align('center')->width(70)->sortable(),
            ImageColumn::make('cover')->label('Cover')->fromMedia('product-cover', 'thumb')->height(40),
            TextColumn::make('name')->label('Name')->sortable()->searchable()->linkAction('edit'),
            BadgeColumn::make('category.title')->label('Category')->pill(),
            BooleanColumn::make('status')->label('Published')->trueLabel('Live')->falseLabel('Draft')->inlineToggle(),
            TextColumn::make('updated_at')->label('Updated')->format(fn ($value) => $value?->format('d.m.Y H:i')),
        ])
        ->filters([
            SelectFilter::make('status')->label('Status')->options([
                'live' => 'Live',
                'draft' => 'Draft',
                'archived' => 'Archived',
            ]),
            DateFilter::make('published_at')->label('Published Between'),
        ])
        ->defaultSort('updated_at', 'desc')
        ->perPageOptions([25, 50, 100]);
}
```

## Form Builder

`public static function form($ctx): Form` defines the create/edit experience. Think in sections: what should the editor focus on first, and what can be collapsed into tabs or panels?

### Planning checklist

1. **Group logically**: put identity fields (name, slug) at the top, content fields in the middle, and status/SEO/media extras towards the end or inside separate tabs.
2. **Use layout components** from [Layouts](10-layouts.md) to keep forms readable. Even a simple `Row` with `Col::make(6)` improves pacing significantly.
3. **Apply validation at the field level** (`required()`, `rules()`, `minLength()`, etc.) so users get immediate feedback.
4. **Reuse helper components**: Fieldset for repeatable data, Media for uploads, BelongsToSelect for relationships.
5. **Turn on `stickyActions()`** for tall forms so the Save button stays visible.
6. **Provide help text** for anything non-trivial.

### Example block

```php
public static function form($ctx): Form
{
    return Form::make()
        ->stickyActions()
        ->schema([
            Tabs::make()->schema([
                Tab::make('Basic')->schema([
                    Row::make()->schema([
                        Col::make(8)->schema([
                            TextInput::make('name')->label('Name')->required()->maxLength(120),
                            Slug::make('slug')->label('Slug')->from('name'),
                            BelongsToSelect::make('category_id')
                                ->label('Category')
                                ->relationship('category', 'title')
                                ->hierarchical()
                                ->nullable(),
                        ]),
                        Col::make(4)->schema([
                            Toggle::make('status')->label('Published')->default(true),
                            DateTimePicker::make('published_at')->label('Publish at'),
                        ]),
                    ]),
                ]),
                Tab::make('Content')->schema([
                    RichEditor::make('body')->label('Description')->height(420),
                    Fieldset::make('features')
                        ->label('Features')
                        ->minItems(1)
                        ->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('body')->rows(2),
                        ])
                        ->headTitle('title')
                        ->addButtonLabel('Add feature'),
                ]),
                Tab::make('Media')->schema([
                    Media::make('cover')->label('Cover')->collection('product-cover')->acceptImages(),
                    Media::make('gallery')->label('Gallery')->collection('product-gallery')->multiple()->acceptImages(),
                ]),
            ]),
        ]);
}
```

## Actions

Resources automatically ship with Edit/Delete row actions, Delete bulk action, and Save/Save & Continue/Cancel form actions. Extend `public static function actions(): array` to register your own row/bulk/global/form actions.

Guidelines:

- Keep business logic inside the action class. The resource should only list them.
- Set `$ability` on the action to match your ACL strategy.
- Provide `confirm()` text for destructive operations.
- For modal actions, return a form schema from `form()` and validation rules from `rules()`.

See [Actions](04-actions.md) for full class references.

## Lifecycle Hooks & Redirects

Use hooks to adjust behaviour without touching controllers:

| Hook | Typical use case |
| --- | --- |
| `beforeCreate(array $data, Request $request)` | Auto-fill author IDs, generate slugs, sanitise payloads. |
| `afterCreate(Model $model, Request $request)` | Queue notifications, log activity, sync external systems. |
| `beforeUpdate(Model $model, array $data, Request $request)` | Protect immutable fields, compute derived values. |
| `afterUpdate(Model $model, Request $request)` | Bust caches, trigger search indexing. |
| `afterDelete(Model $model, Request $request)` | Clean up media/related records. |
| `getIndexRedirectParams(Model $model, Request $request, string $mode)` | Keep filters/tab selections after save (`return ['status' => $model->status];`). |
| `syncRelations(Model $model, array $data, Request $request)` | Handle has-many relationships that aren’t covered by built-in fields. |

All hooks run inside the same database transaction as the save/delete action.

## Search, Sorting & Criteria

- Use `->searchable()` on columns you want to include in the global search box. Override `static::$searchable` when you need cross-table search.
- Use `->sortable()` and `->defaultSort()` to keep results predictable.
- Override `public static function getCriteria(): array` to append custom query modifiers. Useful examples include `QuickTagCriterion`, `DateRangeCriterion`, or bespoke scopes (e.g. “show only records owned by the current user”).
- Each filter declared via `Table::filters()` automatically injects a `TableFilterCriterion`.

## Navigation & Permissions

- Every resource slug maps to abilities like `${slug}.viewAny`, `${slug}.create`, `${slug}.update`, `${slug}.delete`. Keep slugs stable so you don’t have to rebuild permissions.
- Call `AccessManager::registerPermissions()` in seeders or rely on automatic registration (happens when the resource boots) to populate the permission matrix.
- Add menu entries pointing to `route('ave.resource.index', ['slug' => Resource::getSlug()])` once the resource is ready.

## QA Checklist Before Launch

- [ ] Table covers all critical information (ID, main label, status, updated timestamp).
- [ ] Table filters and default sort order make sense for the business workflow.
- [ ] Form sections are clear, required fields have validation, optional sections have help text.
- [ ] Actions have confirmations and respect permissions.
- [ ] Hooks handle derived fields and clean-up routines.
- [ ] Roles/permissions updated to grant the intended teams access.
- [ ] Menu entry added and positioned correctly.

Run through this list each time you deliver a new resource or a significant update.
