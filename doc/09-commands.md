# Commands & Tooling

A few artisan commands and publish targets cover nearly every workflow. Use this section as your operations cheat sheet.

## Artisan Commands

### `ave:install`

Install or refresh Ave inside your application:

```bash
php artisan ave:install [--force] [--no-migrate]
```

- Publishes `config/ave.php`, migrations, assets, translations, and views.
- Runs migrations unless you pass `--no-migrate`.
- Prints next steps (create a resource, visit `/admin`).

Re-run the command after updating dependencies to sync assets/migrations.

### `ave:resource`

Scaffold a resource from an existing model:

```bash
php artisan ave:resource Post
php artisan ave:resource App\\Models\\Product --force
php artisan ave:resource --all
```

Options:

| Option | Description |
| --- | --- |
| `model` argument or `--model=` | Model class or short name (defaults to `App\Models\{Name}`). |
| `--all` | Scan `app/Models` and generate resources for every model (skipping built-in Ave models). |
| `--force` | Overwrite existing resource files. |

The command creates `app/Ave/Resources/{Model}/Resource.php`, fills basic columns/fields, and attempts to add a menu entry for the default menu.

### Testing Helpers

Run the package tests from its root directory (adjust `<app>` to your application folder):

```bash
# Windows / OSPanel example
E:/OSPanel-v6/modules/PHP-8.3/PHP/php.exe ../<app>/vendor/bin/phpunit
```

Run application-level feature tests from the project root:

```bash
E:/OSPanel-v6/modules/PHP-8.3/PHP/php.exe artisan test
```

`run-tests.bat` is available for quick Windows runs.

## Vendor Publish Tags

Run these commands from your application directory:

| Tag | Contents |
| --- | --- |
| `ave-config` | `config/ave.php` (route prefix, ACL, pagination, storage). |
| `ave-assets` | Compiled CSS/JS from `dist/`. Use `--force` when upgrading. |
| `ave-views` | Blade templates under `resources/views/vendor/ave`. |
| `ave-lang` | Translation files (`lang/vendor/ave`). |
| `ave-stubs` | Artisan stubs under `stubs/ave`. |
| `ave-migrations` | Database migrations for ACL, menus, media, etc. |

```bash
php artisan vendor:publish --tag=ave-config
php artisan vendor:publish --tag=ave-assets --force
php artisan vendor:publish --tag=ave-views
```

## Frontend Tooling

If you customise the UI, run the build pipeline from the package root:

```bash
npm install
npm run dev   # watch mode
npm run build # production build, outputs to dist/
```

Publish the rebuilt assets afterward (`php artisan vendor:publish --tag=ave-assets --force`).

## Common Workflows

1. **Update dependencies** — pull the latest changes, run `composer update`, re-run `ave:install --force`, publish assets/migrations, run tests.
2. **Create a new resource** — run `ave:resource`, finish the generated `Resource.php`, wire permissions, validate in the browser.
3. **Adjust ACL** — edit `config/ave.php`, clear caches (`php artisan config:clear`), rerun seeders or manual role assignments.
4. **Debug UI** — publish views, edit the copies under `resources/views/vendor/ave`, and rebuild assets if you touch JS/CSS.
