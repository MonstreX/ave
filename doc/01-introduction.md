# Introduction

This guide is for people who build and manage admin resources every day. By the time you finish this chapter you will know where everything lives, which commands to run, and how to ship a fully working resource from scratch.

## Quick Orientation

- **Resource classes** belong in `app/Ave/Resources/...`. Each folder contains a `Resource.php` plus any nearby helpers (e.g. `Table.php`, `Form.php`, `Actions`).
- **Models** remain in `app/Models`. Resources do not replace your Eloquent layer—they describe how administrators interact with it.
- **Admin routes** live under the prefix from `config/ave.php` (default `/admin`). Visiting `/admin/{resource-slug}` opens a resource table; `/admin/{resource-slug}/create` opens the form.
- **Assets & views** are customisable via `vendor:publish` tags (documented in [Commands & Tooling](09-commands.md)).

You never touch framework internals to add screens: define a resource class and Ave handles discovery, routing, rendering, and persistence.

## Requirements & Setup

1. PHP 8.2+, Laravel 12+, and a UTF‑8 database.
2. Install Ave once per project:
   ```bash
   php artisan ave:install            # publishes config/assets/migrations
   php artisan migrate                # run the new tables if not already executed
   ```
3. If you plan to customise UI, install Node 18+ and run `npm install && npm run build` inside the package before publishing assets.

Useful publish commands (run whenever you need overrides):

```bash
php artisan vendor:publish --tag=ave-config
php artisan vendor:publish --tag=ave-views
php artisan vendor:publish --tag=ave-lang
php artisan vendor:publish --tag=ave-assets --force
```

## Daily Workflow Overview

1. **Plan**  
   - Decide which model the resource controls.
   - List the columns, filters, and actions administrators need.
   - Decide the form layout (tabs? sections? repeaters? media?).
2. **Scaffold**  
   - Optional: run `php artisan ave:resource ModelName` to generate a starting `Resource.php`.  
   - Move into the new folder under `app/Ave/Resources/ModelName`.
3. **Build**  
   - Fill `static $model`, `$label`, `$slug`, `$icon`, and other metadata.  
   - Implement `table()` and `form()` step by step (see [Resources](02-resources.md)).  
   - Add actions, filters, fields, and lifecycle hooks as needed.
4. **Verify**  
   - Visit `/admin/{slug}` to inspect the table and form.
   - Run smoke tests with the built-in PHPUnit suite if you added critical logic.
5. **Publish**  
   - Register permissions/roles (see [Permissions](08-permissions.md)).  
   - Expose the resource in the admin menu or dashboard.

Keeping this loop tight ensures every change is reviewable and shippable.

## Creating Your First Resource

Follow these steps the first time you add a resource:

1. **Generate the folder (optional but recommended).**
   ```bash
   php artisan ave:resource Product
   ```
   This creates `app/Ave/Resources/Product/Resource.php` with minimal table/form definitions.

2. **Set core metadata.**
   ```php
   class Resource extends BaseResource
   {
       public static ?string $model = \App\Models\Product::class;
       public static ?string $label = 'Products';
       public static ?string $singularLabel = 'Product';
       public static ?string $slug = 'products';
       public static ?string $icon = 'voyager-bag';
   }
   ```

3. **Define the table view.**
   ```php
   public static function table($ctx): Table
   {
       return Table::make()
           ->columns([
               TextColumn::make('id')->label('ID')->sortable(),
               ImageColumn::make('cover')->label('Cover')->fromMedia('main', 'thumb'),
               TextColumn::make('name')->label('Name')->sortable()->searchable()->linkAction('edit'),
               BadgeColumn::make('status')->label('Status')->colors([
                   'draft' => 'warning',
                   'active' => 'success',
               ]),
               BooleanColumn::make('is_featured')->label('Featured')->inlineToggle(),
           ])
           ->filters([
               SelectFilter::make('status')->label('Status')->options([
                   'active' => 'Active',
                   'draft' => 'Draft',
               ]),
           ])
           ->defaultSort('created_at', 'desc');
   }
   ```

4. **Define the form.**
   ```php
   public static function form($ctx): Form
   {
       return Form::make()
           ->stickyActions()
           ->schema([
               Tabs::make()->schema([
                   Tab::make('Basic')->schema([
                       TextInput::make('name')->label('Name')->required()->maxLength(120),
                       Slug::make('slug')->label('Slug')->from('name'),
                       Number::make('price')->label('Price')->minValue(0)->suffix('EUR'),
                       Toggle::make('status')->label('Published')->default(true),
                   ]),
                   Tab::make('Media')->schema([
                       Media::make('cover')->label('Cover Image')->collection('product-cover')->acceptImages(),
                       Media::make('gallery')->label('Gallery')->collection('product-gallery')->multiple()->acceptImages(),
                   ]),
                   Tab::make('Details')->schema([
                       RichEditor::make('description')->label('Description')->height(400),
                       Fieldset::make('features')
                           ->label('Feature List')
                           ->schema([
                               TextInput::make('title')->label('Title')->required(),
                               Textarea::make('body')->label('Body')->rows(2),
                           ])
                           ->headTitle('title')
                           ->addButtonLabel('Add feature'),
                   ]),
               ]),
           ]);
   }
   ```

5. **Register actions (optional).**
   ```php
   public static function actions(): array
   {
       return [
           Actions\PublishProductAction::class,
           Actions\DuplicateProductAction::class,
       ];
   }
   ```

6. **Test in the browser.** Visit `/admin/products`. Use the table search, filters, and actions to ensure everything behaves as intended.

7. **Wire permissions and menu entries.** Use `AccessManager` helpers or seeders so only the intended roles can access the resource, then add the slug to your admin menu.

## What to Read Next

- Move to [Resources](02-resources.md) for a deep dive into every static property, lifecycle hook, and discovery rule.
- Jump to [Fields](03-fields.md) and [Complex Fields](13-complex-fields.md) for input-level details.
- When you need to understand or extend the internals, open [Developer Architecture](14-developer-architecture.md).
