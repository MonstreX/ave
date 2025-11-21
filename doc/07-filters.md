# Filters & Criteria

Filtering determines which records appear in the table. Ave splits the responsibility between **filters** (UI controls declared on `Table`) and **criteria** (query modifiers that read request parameters). The glue between them is `CriteriaPipeline` (`src/Core/Criteria/CriteriaPipeline.php`).

## Table Filters (UI)

Declare UI filters directly on the table definition:

```php
use Monstrex\Ave\Core\Filters\SelectFilter;
use Monstrex\Ave\Core\Filters\DateFilter;

Table::make()
    ->filters([
        SelectFilter::make('status')
            ->label('Publication Status')
            ->options(['1' => 'Published', '0' => 'Draft'])
            ->default('1'),
        SelectFilter::make('category_id')
            ->label('Category')
            ->options(static::categoryOptions()),
        DateFilter::make('published_at')
            ->label('Published Between'),
    ]);
```

Built-in filter classes:

| Class | Behaviour |
| --- | --- |
| `SelectFilter` | Dropdown (single or multi-select). Accepts `options(array $map)` and `multiple()` to allow multi-value queries. |
| `DateFilter` | Date picker that can operate on exact values or `from`/`to` ranges. |

Filters emit their value as query parameters (e.g. `?filter[status]=1`). The `TableFilterCriterion` reads those parameters and applies them to the query.

### Custom filter UI

Create a class that extends `Monstrex\Ave\Core\Filters\Filter` and implement:

- `apply(Builder $query, mixed $value)` — modify the query.
- `toArray()` — return metadata consumed by the frontend.
- Optional `formatBadgeValue()` — controls how badges display the active filter.

Attach it to `Table::filters()` and `CriteriaPipeline` automatically creates a `TableFilterCriterion`.

## Criteria

Criteria are query modifiers invoked on every request. The pipeline sorts them by priority and applies them to the builder. By default it includes:

| Criterion | Description |
| --- | --- |
| `SearchCriterion` | Reads the `search` query parameter and adds `whereLike` clauses for `Resource::searchableColumns()`. |
| `SortCriterion` | Applies `orderBy` using request parameters or `Table::defaultSort()`. |
| `SoftDeleteCriterion` | Exposes `?trashed=with|only` when the model uses `SoftDeletes`. |
| `TableFilterCriterion` | Converts table filter values into query clauses. |

Add your own criteria by overriding `Resource::getCriteria()`:

```php
use Monstrex\Ave\Core\Criteria\QuickTagCriterion;

public static function getCriteria(): array
{
    return [
        QuickTagCriterion::class,           // class name (auto-instantiated)
        new \App\Support\Criteria\ActiveCriterion(), // resolved instance
    ];
}
```

Implement `Monstrex\Ave\Core\Criteria\Contracts\Criterion` or extend `AbstractCriterion`. Required methods:

- `apply(Builder $query, Request $request): Builder`
- `badge(Request $request): ?ActionBadge` — optional; used to render “active filter” badges over the table.
- `priority(): int` — lower numbers run first (defaults to `100` in `AbstractCriterion`).

## Filter Badges

`CriteriaPipeline::badges()` gathers badge descriptors from each criterion. Each badge includes a label and value (human readable). They appear above the table in the UI so users can see which filters are active. Override `Filter::formatBadgeValue()` or `Criterion::badge()` to customise the display string.

## Practical Examples

- **Article resource** — uses `SelectFilter` for status/category and `DateFilter` for a range. The query also respects inline search and default sorting.
- **Ave Site Page resource** — `Table::tree()` plus filters for status make it easy to find nested pages.

## Tips

- **Always whitelist options** — pass associative arrays to `SelectFilter::options()` so badges show friendly labels.
- **Use `multiple()` for tags/categories** — combine with `whereIn` logic inside a custom filter.
- **Expose shortcuts** — implement a custom criterion that looks at query strings such as `?featured=1` so you can link to pre-filtered views.
- **Remember pagination state** — filters live in the query string, so they survive pagination and export actions automatically.
