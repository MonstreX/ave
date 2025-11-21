# Relationships

Resources frequently need to display or edit related models. Ave provides dedicated fields, columns, and helper methods to make this convenient while keeping queries efficient.

## Displaying Relations

- **Dot notation** — any column can access nested attributes: `TextColumn::make('category.title')->label('Category')`.
- **Counts** — set `public static array $withCount = ['comments'];` and use `TextColumn::make('comments_count')`.
- **Badges** — use `BadgeColumn` with relation keys to render tags/categories.
- **Tree view** — `Table::tree('parent_id', 'order')` pairs nicely with `BelongsToSelect::hierarchical()` to show parent-child relationships.

Always configure `$with`/`$withCount` on the resource to avoid N+1 queries when showing relation data.

## BelongsToSelect

`Monstrex\Ave\Core\Fields\BelongsToSelect` is tailored for foreign keys. It loads options from the related model and handles nullable states.

```php
BelongsToSelect::make('category_id')
    ->label('Category')
    ->relationship('category', 'title')
    ->nullable()
    ->hierarchical()      // show indentation for parent/child relations
    ->searchable()        // enable AJAX search
    ->active();           // scope to active categories (see below)
```

Features:

- **`relationship($name, $labelColumn)`** — relation name from the model and which column to show in the dropdown.
- **`hierarchical()`** — turns options into a tree. Requires the related model to have `parent_id` and `order` columns. The depth is represented via non-breaking space indentation.
- **`modifyQuery(Closure $callback)`** — adjust the options query (filters, ordering). Helpers such as `active()` live in `HasRelationQueryModifiers`.
- **`nullable()`** — adds `nullable` validation and a blank option.
- **`searchable()`** — toggles client-side search; the field still loads options upfront, so use query modifiers to limit the dataset when necessary.
- **`default(int|string $id)`** — pre-selects a relation on create forms.

## BelongsToManySelect

Handles pivot tables using deferred persistence.

```php
BelongsToManySelect::make('tags')
    ->label('Tags')
    ->relationship('tags', 'name')
    ->searchable()
    ->optionsLimit(500); // default is 500
```

Important behaviour:

- Values are stored as an array of related IDs.
- During `prepareForDisplay()` the field loads the IDs via the relation.
- During `prepareForSave()` it registers a deferred action that calls `$model->tags()->sync($ids)` after the parent model is saved.
- Use `modifyQuery()` to filter the selectable tags (e.g., only active tags).

## HasMany / Morph Relations

Ave does not ship dedicated fields for `HasMany` editing. Use one of these patterns:

1. **Fieldset + JSON column** — store child records as JSON blobs (see Ave Site Form Resource).
2. **Separate resource** — create another resource for the child model and use `BelongsToSelect` to link back to the parent.
3. **Custom field** — extend `AbstractField` when you need a bespoke UI (e.g., complex pivot models).

Morph relations behave like standard ones as long as your Eloquent model exposes helper methods. For editing, combine a `Select` for the morph type and a `BelongsToSelect` for the ID.

## Relation Map

`public static array $relationMap` is an optional metadata array resource authors can use to describe nested resources (e.g., `'comments' => CommentResource::class`). The framework does not currently enforce behaviour based on this map, but it can be used by custom tooling to build breadcrumbs or nested navigation.

## Filtering by Relations

Two options:

- Add a `SelectFilter` plus a matching custom criterion that joins the related table.
- Use `QuickTagCriterion` or your own criterion to perform scoped queries (e.g., filter posts by tag IDs).

Example criterion:

```php
class CategoryCriterion extends AbstractCriterion
{
    public function apply(Builder $query, Request $request): Builder
    {
        $category = $request->input('category_id');
        return $category ? $query->where('category_id', $category) : $query;
    }

    public function badge(Request $request): ?ActionBadge
    {
        if (! $request->filled('category_id')) {
            return null;
        }

        return ActionBadge::make('Category', Category::find($request->input('category_id'))?->title);
    }
}
```

## Real-World Examples

- **Article Resource** — uses `BelongsToSelect` (hierarchical categories) and `BelongsToManySelect` (tags), and shows relation data in columns/badges.
- **Ave Site Block resources** — rely heavily on Fieldsets with nested Media to model region/slot relationships.

Use these as templates for your own resources; most relationship use cases are already covered there.
