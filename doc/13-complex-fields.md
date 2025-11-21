# Complex Fields

Some inputs do far more than map a single attribute to a single column. This chapter documents the heavy hitters—`Fieldset`, `Media`, `RichEditor`, and `CodeEditor`—and shows how they interact with deferred persistence, nested state paths, and presets.

## Fieldset (Repeatable Groups)

`Monstrex\Ave\Core\Fields\Fieldset` is a JSON-backed repeater. Each item is a mini form that can contain any combination of fields (except another Fieldset). All logic lives in `src/Core/Fields/Fieldset.php`.

```php
Fieldset::make('features')
    ->schema([
        TextInput::make('title')->label('Title')->required(),
        Textarea::make('description')->label('Description')->rows(2),
        Media::make('image')->collection('article-feature')->acceptImages()->single(),
    ])
    ->headTitle('title')
    ->minItems(1)
    ->maxItems(8)
    ->sortable()
    ->collapsible()
    ->columns(2)
    ->addButtonLabel('Add feature')
    ->deleteButtonLabel('Remove feature');
```

### Behaviour

- **Storage** — Fieldset values are stored as an array in a JSON column (`text`, `jsonb`, etc.). `Fieldset::extract()` ensures only meaningful items persist (empty rows are dropped unless `preserveEmptyItems()` is set).
- **Stable IDs** — Every item receives a deterministic `_id`. Drag-and-drop sorting and nested media cleanup rely on these IDs, so do not remove them manually.
- **Nested state paths** — `getItemStatePath()` composes paths such as `features.0.image`, which allows Media fields inside a Fieldset to generate unique collection meta keys.
- **Deferred actions** — Nested fields that implement `HandlesNestedCleanup` (e.g. `Media`) register clean-up closures so deleting a Fieldset item also removes its media collection.
- **Validation** — `minItems()`/`maxItems()` and `rules(['array'])` run before persistence. Children contribute their own validation rules through the normal mechanism.
- **Templates & Headers** — `headTitle('title')` uses a child field value as the collapsed row title; `headPreview('slug')` adds a secondary line. Use `columns(int $cols)` (1–4) to control how the item editor is laid out.
- **No nested Fieldsets** — `validateNoNestedFieldsets()` throws `FieldsetNestingException` if a Fieldset is found anywhere inside `schema()`. This keeps data shapes manageable.

### Runtime Flow

1. `prepareRequest()` normalises posted JSON, assigns IDs, and feeds the data into an internal `RequestProcessor`.
2. `prepareForSave()` iterates over child fields, collects payload and deferred actions, and returns a `FieldPersistenceResult`.
3. After the parent model is saved, `FormContext->runDeferredActions()` executes cleanup/attach logic for nested fields.

Review the bundled resources (e.g. Articles, Menu Items, Blocks) to see how Fieldset + Media power real-world screens.

## Media Field

`Monstrex\Ave\Core\Fields\Media` provides a full media management UI. It speaks to `MediaController`, `MediaRepository`, and `Media\Facades\Media` to handle uploads, ordering, metadata, and cleanup. The default collection name is the field key, but nested fields automatically generate state-path-aware names so Fieldset items do not collide.

```php
Media::make('main_image')
    ->collection('article-main')
    ->acceptImages()
    ->multiple(false)
    ->conversions([
        'thumb' => ['width' => 200, 'height' => 200, 'fit' => 'cover'],
        'preview' => ['width' => 1024, 'height' => 768],
    ])
    ->props('title', 'alt', 'caption')
    ->columns(8)
    ->preset(SingleImagePreset::class);
```

### Key Features

| Capability | Description |
| --- | --- |
| **Collections & Overrides** | `collection('gallery')` sets the logical bucket. `useCollectionOverride()` lets you force a specific collection even when the state path would normally influence it. |
| **Multiple files** | `multiple(true, $maxFiles)` toggles multi-upload. Set `maxFiles()` independently to enforce another limit. |
| **Accepted files** | `accept()` allows custom MIME arrays; `acceptImages()` / `acceptDocuments()` are shortcuts. |
| **File size & count** | `maxFileSize()` for KB limits, `maxFiles()` for quantity, `maxImageSize()` auto-resizes large images on upload. |
| **Presets** | `preset(SingleImagePreset::class)` / `GalleryPreset` / `DocumentsPreset` / `IconsPreset` configure multiple options at once (columns, conversions, props, layout variant). |
| **Metadata** | `props('alt', 'title')` exposes editable metadata per media item; data is stored in the `props` JSON column. |
| **Path strategies** | Configure storage via `pathStrategy('dated'|'flat')`, `pathPrefix('media/articles')`, or provide a custom `pathGenerator` closure. The disk and filename strategies come from `config/ave.php`. |
| **Deferred persistence** | `prepareForSave()` collects uploaded temporary files, waits until the parent model exists, then attaches media by ID. Nested clean-up closures ensure removing Fieldset items deletes their media collection. |

The upload endpoint lives at `POST /admin/media/upload` and is already registered via `RouteRegistrar`. Assets are published under the `ave-assets` tag.

## RichEditor & CodeEditor

Both editors extend `AbstractField` and pull in preset classes from `src/Core/Fields/Presets`. They share a similar API for predictable configuration.

### RichEditor

Powered by Jodit, configured via PHP:

```php
use Monstrex\Ave\Core\Fields\RichEditor;
use Monstrex\Ave\Core\Fields\Presets\RichEditor\MinimalPreset;

RichEditor::make('content')
    ->label('Content')
    ->height(500)
    ->preset(MinimalPreset::class)
    ->enable(['code', 'lists'])
    ->disable(['image'])
    ->options(['uploader' => ['insertImageAsBase64URI' => true]]);
```

- Presets (`MinimalPreset`, `BasicPreset`, `FullPreset`) control toolbar groups and default options.
- `height()` sets a fixed height; `autoHeight()` (see preset) adapts to content.
- `enable()` / `disable()` accept feature tokens: `headings`, `paragraph`, `bold`, `italic`, `underline`, `strike`, `lists`, `links`, `images`, `tables`, `blockquote`, `code`, `inline-styles`, `undo`, `redo`, `source`, `font`, `fontsize`, `brush`, `hr`.
- Arbitrary Jodit config can be merged through `options(array $config)`.

### CodeEditor

`CodeEditor` provides a syntax-highlighted editor with language/theme presets (Monaco-based).

```php
use Monstrex\Ave\Core\Fields\CodeEditor;
use Monstrex\Ave\Core\Fields\Presets\CodeEditor\JsonPreset;

CodeEditor::make('options')
    ->label('Options (JSON)')
    ->preset(JsonPreset::class)
    ->language('json')
    ->theme('github')
    ->autoHeight(true)
    ->height(240);
```

- Presets (`JsonPreset`, `HtmlPreset`, etc.) set sensible defaults for mode, theme, and validation hints.
- `language()` and `theme()` accept any language/theme supported by the bundled editor assets.
- `autoHeight()` grows with content until it reaches `maxHeight` (configurable via `options()`).

Both editors appear in multiple live resources:

- Article content (`RichEditor` + `CodeEditor` for JSON options).
- Ave Site Page resource (`CodeEditor` for “details” JSON, `RichEditor` for body HTML).

## Putting It Together

Complex fields shine when combined:

```php
Tabs::make()->schema([
    Tab::make('Content')->schema([
        RichEditor::make('body')->label('Body'),
        Fieldset::make('features')
            ->schema([
                TextInput::make('title')->required(),
                Media::make('image')->collection('feature')->acceptImages()->single(),
                Textarea::make('description'),
            ])
            ->headTitle('title')
            ->preserveEmptyItems(false),
    ]),
    Tab::make('Media')->schema([
        Media::make('gallery')
            ->multiple()
            ->maxFiles(12)
            ->acceptImages()
            ->preset(GalleryPreset::class),
    ]),
]);
```

Use these patterns liberally—`ResourcePersistence` and `FormContext` were designed to handle them without additional controllers or manual request parsing.
