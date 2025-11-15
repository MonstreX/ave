# Ave Admin Panel

A modern, lightweight admin panel package for Laravel with declarative component-based architecture.

## Features

- **Declarative Resources** - Define admin panels with clean, intuitive PHP classes
- **Advanced ACL** - Granular permissions with roles, groups, and resource-level control
- **Media Library** - Powerful file management with multiple upload strategies and image processing
- **Fieldsets** - Reusable, composable field groups with conditional logic
- **Modal CRUD** - Create and edit records in modals without page reload
- **Hierarchical Data** - Built-in support for nested/tree structures (menus, categories)
- **Rich Components** - Advanced fields (WYSIWYG, CodeMirror, BelongsTo with search, etc.)
- **Performance** - Optimized with caching, lazy loading, and minimal queries

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Node.js 18+ and NPM (only for development/asset compilation)

## Installation

### Quick Installation (Recommended)

Install the package via Composer and run the install command:

```bash
composer require monstrex/ave
php artisan ave:install
```

The install command will automatically:
- Publish configuration files
- Publish compiled assets (CSS, JS, fonts, images)
- Publish and run migrations
- Show you the next steps

**Options:**
```bash
php artisan ave:install --force        # Overwrite existing files
php artisan ave:install --no-migrate   # Skip running migrations
```

That's it! Visit `/admin` in your browser to access the admin panel.

### Manual Installation (Advanced)

If you prefer manual control over the installation process:

#### Step 1: Install Package

```bash
composer require monstrex/ave
```

The service provider will be automatically registered via Laravel's package auto-discovery.

#### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=ave-config
```

This creates `config/ave.php` where you can customize:
- Route prefix (default: `admin`)
- User model and table
- Access control settings
- Cache configuration
- And more...

#### Step 3: Publish Assets

```bash
php artisan vendor:publish --tag=ave-assets --force
```

Assets will be published to `public/vendor/ave/`.

#### Step 4: Publish Migrations

```bash
php artisan vendor:publish --tag=ave-migrations
```

#### Step 5: Run Migrations

```bash
php artisan migrate
```

This creates:
- `ave_roles` - User roles
- `ave_role_user` - Role assignments
- `ave_groups` - Permission groups
- `ave_group_role` - Group-role relationships
- `ave_permissions` - Resource permissions
- `ave_media` - Media files
- Adds `locale` column to users table

### Create Admin Resources

Create your first resource by creating a class in `app/Ave/Resources`:

```php
<?php

namespace App\Ave\Resources;

use Monstrex\Ave\Admin\BaseResource;
use Monstrex\Ave\Core\Components\Fields\TextInput;
use Monstrex\Ave\Core\Components\Columns\Column;
use App\Models\Post;

class PostResource extends BaseResource
{
    public static string $model = Post::class;
    protected static ?string $slug = 'posts';
    protected static ?string $icon = 'voyager-news';

    public function fields(): array
    {
        return [
            TextInput::make('title')
                ->label(__('Title'))
                ->required(),

            TextInput::make('content')
                ->label(__('Content'))
                ->required(),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('id')->label(__('ID')),
            Column::make('title')->label(__('Title')),
            Column::make('created_at')->label(__('Created')),
        ];
    }
}
```

The resource will be automatically discovered and registered.

## Configuration

### Route Prefix

By default, Ave admin panel is accessible at `/admin`. Change this in `config/ave.php`:

```php
'route_prefix' => 'admin',
```

### User Model

Specify your user model and table:

```php
'user_model' => \App\Models\User::class,
'users_table' => 'users',
```

### Access Control

Enable or disable the ACL system:

```php
'acl_enabled' => true,
```

### Caching

Configure resource discovery caching:

```php
'cache_discovery' => true,
'cache_ttl' => 3600, // seconds
```

Clear cache when needed:

```bash
php artisan ave:cache-clear
```

## Localization

Ave comes with English and Russian translations. Users can switch languages via the navbar selector.

### User Locale Preference

Each user's language preference is stored in the `locale` column of the users table and automatically applied on login.

### Publishing Translations

To customize translations, publish the language files:

```bash
php artisan vendor:publish --tag=ave-lang
```

Files will be published to `lang/vendor/ave/`.

### Adding New Languages

1. Create a new directory in `lang/vendor/ave/` (e.g., `fr` for French)
2. Copy translation files from `en` or `ru` directory
3. Translate the strings
4. Update the `$localeNames` array in `resources/views/partials/navbar.blade.php`

## Customizing Views

Publish the Blade templates to customize the UI:

```bash
php artisan vendor:publish --tag=ave-views
```

Views will be published to `resources/views/vendor/ave/`.

## Publishing Migrations

If you need to customize the database structure, publish migrations:

```bash
php artisan vendor:publish --tag=ave-migrations
```

Migrations will be published to `database/migrations/`.

## Development

### Building Assets

If you need to modify the package's CSS or JavaScript:

1. Navigate to the package directory
2. Install dependencies: `npm install`
3. Make your changes in `resources/css/` or `resources/js/`
4. Build assets: `npm run build` (production) or `npm run dev` (development)

### Running Tests

The package includes a comprehensive test suite:

```bash
cd vendor/monstrex/ave
php ../../../vendor/bin/phpunit
```

## Components

Ave provides a rich set of components for building admin interfaces:

### Advanced Fields

- **`MediaUpload`** - Powerful file/image upload with drag-n-drop, preview, multiple files support
- **`Fieldset`** - Reusable field groups with conditional visibility and nested structures
- **`BelongsToSelect`** - Smart relation field with search, hierarchical data, and lazy loading
- **`WysiwygEditor`** - Rich text editing with TinyMCE integration
- **`CodeEditor`** - Syntax-highlighted code editing with CodeMirror
- `TextInput`, `TextArea`, `Select`, `Checkbox`, `DatePicker` - Standard form inputs
- `Toggle`, `Radio`, `Hidden` - Additional input types

### Table Columns

- `Column` - Basic text column with sorting and formatting
- `ImageColumn` - Image thumbnail from media library
- `BooleanColumn` - Visual status indicator
- `DateColumn` - Formatted date/time display
- `RelationColumn` - Display related model data

### Actions

- `CreateInModalAction`, `EditInModalAction` - Modal-based CRUD without page reload
- `DeleteAction` - Safe deletion with confirmation
- `CustomAction` - Build your own actions with custom logic

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

Developed by [Monstrex](https://github.com/monstrex).

## Support

For issues, feature requests, or questions, please use the [GitHub issue tracker](https://github.com/monstrex/ave/issues).
