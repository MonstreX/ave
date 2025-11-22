# Ave Admin Panel

A modern, declarative admin panel for Laravel with advanced media management and composable field architecture.

## Key Features

- **Advanced Media System** - State-path based collections, deferred actions, multiple upload strategies, image conversions, drag-n-drop reordering
- **Fieldset Component** - Repeatable field groups stored in JSON with nested media support, drag-n-drop sorting, and automatic cleanup
- **Hierarchical Relations** - BelongsToSelect with automatic parent-child tree building and visual indentation
- **Modal CRUD** - Create and edit records in modals without page reload
- **Inline Editing** - Edit table cells directly with text, number, and toggle support
- **Rich Components** - 23 field types including RichEditor (Jodit), CodeEditor, hierarchical selects
- **Granular ACL** - Role-based permissions with groups, bulk operations, caching, and default roles

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher

## Installation

Install via Composer and run the setup command:

```bash
composer require monstrex/ave
php artisan ave:install
```

This will:
- Publish configuration and assets
- Run migrations
- Set up the admin panel at `/admin`

**Options:**
```bash
php artisan ave:install --force        # Overwrite existing files
php artisan ave:install --no-migrate   # Skip migrations
```

## Uninstallation

To completely remove Ave Admin Panel from your application:

```bash
php artisan ave:uninstall
```

This will:
- Drop all Ave database tables
- Delete published config and assets
- Remove admin users (optional)
- Clear Ave cache entries

**Options:**
```bash
php artisan ave:uninstall --dry-run        # Preview what would be deleted
php artisan ave:uninstall --force          # Skip confirmation
php artisan ave:uninstall --keep-users     # Don't delete admin users
php artisan ave:uninstall --keep-config    # Keep published config
php artisan ave:uninstall --keep-assets    # Keep published assets
```

**Note:** After uninstalling, remove the package from composer:
```bash
composer remove monstrex/ave
```

## Quick Start

Create a resource in `app/Ave/Resources`:

```php
<?php

namespace App\Ave\Resources;

use Monstrex\Ave\Admin\BaseResource;
use Monstrex\Ave\Core\Components\Fields\TextInput;
use Monstrex\Ave\Core\Components\Fields\Media;
use Monstrex\Ave\Core\Components\Columns\Column;
use Monstrex\Ave\Core\Components\Columns\ImageColumn;
use App\Models\Post;

class PostResource extends BaseResource
{
    public static string $model = Post::class;
    protected static ?string $slug = 'posts';

    public function fields(): array
    {
        return [
            TextInput::make('title')->required(),
            Media::make('cover')->single()->acceptImages(),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('title')->sortable()->searchable(),
            ImageColumn::make('cover')->fromMedia('cover', 'thumb'),
        ];
    }
}
```

Visit `/admin/posts` to see your resource.

## Components

### Fields (23 types)

**Text Inputs:**
- `TextInput` - Single-line with type variants (email, url, tel, number), prefix/suffix, validation
- `Textarea` - Multi-line text
- `Number` - Numeric input with min/max/step
- `PasswordInput` - Password field
- `Hidden` - Hidden field

**Rich Editors:**
- `RichEditor` - WYSIWYG editor powered by Jodit with toolbar presets (minimal/basic/full), feature toggles, and customizable height
- `CodeEditor` - Syntax-highlighted code editing with modes (html, css, js, php, json, xml, sql, markdown, yaml) and themes (monokai, github, twilight)

**Selects:**
- `Select` - Dropdown with options array
- `BelongsToSelect` - Relation selector with **hierarchical support** (automatic parent-child tree building), query modifiers, nullable
- `BelongsToManySelect` - Multiple selection for many-to-many relations with automatic pivot sync
- `CheckboxGroup` - Group of checkboxes
- `RadioGroup` - Radio button group

**Toggles:**
- `Checkbox` - Single checkbox
- `Toggle` - Switch toggle

**Specialized:**
- `DateTimePicker` - Date and time selection
- `ColorPicker` - Color picker
- `Tags` - Tag input
- `Slug` - Auto-generate slug from another field

**Files & Media:**
- `File` - Document uploads with MIME type filtering, filename strategies (original, transliterate, unique), path strategies (flat, dated, custom)
- `Media` - **Advanced media library** with:
  - Single/multiple files (with maxFiles limit)
  - Drag & drop upload and reordering
  - Image conversions (thumbnail, medium, large)
  - Metadata (title, alt, caption, position, custom props)
  - Collections for grouping
  - **Preset system**: SingleImagePreset, GalleryPreset, DocumentsPreset, IconsPreset
  - **State-path based collection naming** for nested fields
  - **Deferred actions** for newly created models
  - Automatic cleanup when deleting Fieldset items

**Containers:**
- `Fieldset` - **Repeatable field groups** stored in JSON with:
  - Add/remove/reorder items (drag & drop)
  - Collapsible/collapsed state
  - Stable IDs for media fields
  - Support for nested Media with deterministic collection names
  - minItems/maxItems limits
  - Custom add/delete button labels
  - Head title/preview from field values
  - **Automatic media cleanup** on item deletion

**Layout:**
- `Row` - Bootstrap grid row
- `Col` - Bootstrap grid column (1-12)
- `Panel` - Panel with header
- `Tabs` / `Tab` - Tabbed interface
- `Div` - Container with conditional visibility
- `Group` - Element grouping

### Columns (7 types)

- `Column` - Universal column with sorting, searching, formatting, alignment, width control, text wrapping, **inline editing**, styling (fontSize, bold, italic, color), and links (linkToEdit, linkUrl, linkAction)
- `TextColumn` - Text display (extends Column)
- `BooleanColumn` - Boolean indicator with custom labels, icons, colors, and **inline toggle editing**
- `ImageColumn` - Image display with sizes, shapes (square, circle), lightbox, single/multiple modes, stack/grid layouts, and **media library integration** with conversion support
- `BadgeColumn` - Badge/tag display
- `ComputedColumn` - Calculated values
- `TemplateColumn` - Custom Blade template

### Actions (8 types)

**Row Actions:**
- `EditAction` - Navigate to edit page
- `EditInModalAction` - Edit in modal without page reload
- `DeleteAction` - Delete with confirmation
- `RestoreAction` - Restore soft-deleted records

**Global Actions:**
- `CreateInModalAction` - Create in modal without page reload

**Form Actions:**
- `SaveFormAction` - Save and return
- `SaveAndContinueFormAction` - Save and continue editing
- `CancelFormAction` - Cancel and return

## Advanced Features

### Media System

The Media field provides enterprise-level file management:

```php
Media::make('gallery')
    ->multiple(true, maxFiles: 20)
    ->acceptImages()
    ->maxFileSize(5120) // KB
    ->columns(8) // Grid layout
    ->props('title', 'alt', 'caption', 'position')
    ->conversions([
        'thumb' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 800],
        'large' => ['width' => 1920],
    ])
    ->pathStrategy('dated')
    ->pathPrefix('products')

// Or use a preset
Media::make('banner')->preset(SingleImagePreset::class)
```

**How it works:**
- Files are stored in `ave_media` table with metadata
- State-path determines collection name (e.g., `gallery` → collection)
- Supports nested fields through meta keys
- Deferred actions handle new model creation
- Automatic cleanup when parent is deleted

### Fieldset with Nested Media

Create repeatable structures with media support:

```php
Fieldset::make('team_members')
    ->schema([
        TextInput::make('name')->required(),
        TextInput::make('position'),
        Media::make('photo')->preset(SingleImagePreset::class),
        Textarea::make('bio'),
    ])
    ->sortable()
    ->collapsible()
    ->minItems(1)
    ->maxItems(10)
    ->headTitle('name') // Use 'name' field as item title
    ->addButtonLabel('Add Team Member')
```

**Features:**
- Stable item IDs for media correlation
- Drag & drop reordering
- Automatic media cleanup on item deletion
- Nested media uses deterministic collection names
- Template fields for dynamic addition

### Hierarchical Relations

Build tree structures in dropdowns:

```php
BelongsToSelect::make('parent_id')
    ->relationship('parent', 'title')
    ->hierarchical() // Requires parent_id and order columns
    ->nullable()
    ->where(fn($q) => $q->where('status', 'active'))
```

Displays as:
```
Root Item
├─ Child Item
│  └─ Grandchild Item
└─ Another Child
```

### Inline Editing

Edit table cells directly:

```php
Column::make('price')
    ->inline('text', ['field' => 'price'])
    ->inlineRules('required|numeric|min:0')

BooleanColumn::make('is_active')
    ->inlineToggle() // Click to toggle
```

### Access Control

Define granular permissions:

```php
// In a seeder or service provider
$accessManager->registerPermissions('posts', [
    'viewAny' => ['name' => 'View Posts List'],
    'view' => ['name' => 'View Post Details'],
    'create' => ['name' => 'Create Posts'],
    'update' => ['name' => 'Edit Posts'],
    'delete' => ['name' => 'Delete Posts'],
]);
```

Check permissions:
```php
$accessManager->allows($user, 'posts', 'create'); // boolean
```

**Features:**
- Role-based with groups for organization
- Super role bypasses all checks
- Default roles for new users
- Bulk permission checks (optimized)
- Caching with configurable TTL

### Lifecycle Hooks

Intercept CRUD operations:

```php
class PostResource extends BaseResource
{
    protected function beforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate($model): void
    {
        // Trigger notifications, etc.
    }

    protected function beforeUpdate($model, array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }
}
```

Available hooks: `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`, `afterDelete`

## Configuration

Customize in `config/ave.php`:

```php
return [
    'route_prefix' => 'admin',
    'user_model' => \App\Models\User::class,
    'users_table' => 'users',

    'acl' => [
        'enabled' => true,
        'super_role' => 'admin',
        'cache_ttl' => 300,
    ],

    'pagination' => [
        'default_per_page' => 25,
        'per_page_options' => [10, 25, 50, 100],
    ],
];
```

## Customization

Publish assets for customization:

```bash
php artisan vendor:publish --tag=ave-views    # Blade templates
php artisan vendor:publish --tag=ave-lang     # Translations (en, ru)
php artisan vendor:publish --tag=ave-config   # Configuration
```

## Development

**Run tests:**
```bash
cd vendor/monstrex/ave
php ../../../vendor/bin/phpunit
```

**Build assets:**
```bash
npm install
npm run build  # or npm run dev
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Developed by [Monstrex](https://github.com/monstrex).

## Support

Report issues at [GitHub issue tracker](https://github.com/monstrex/ave/issues).
