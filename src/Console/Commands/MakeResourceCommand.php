<?php

namespace Monstrex\Ave\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class MakeResourceCommand extends Command
{
    protected $signature = 'ave:resource
                            {model? : The model class name (e.g., Post or App\Models\Post)}
                            {--all : Generate resources for all models}
                            {--force : Overwrite existing resource files}';

    protected $description = 'Generate Ave admin resource from Eloquent model';

    protected array $excludeModels = [
        'Monstrex\Ave\Models\Media',
        'Monstrex\Ave\Models\Role',
        'Monstrex\Ave\Models\Permission',
        'Monstrex\Ave\Models\Group',
        'Monstrex\Ave\Models\Menu',
        'Monstrex\Ave\Models\MenuItem',
    ];

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->generateAll();
        }

        $modelInput = $this->argument('model');

        if (!$modelInput) {
            $this->error('Please provide a model name or use --all flag');
            return self::FAILURE;
        }

        $modelClass = $this->resolveModelClass($modelInput);

        if (!$modelClass) {
            $this->error("Model [{$modelInput}] not found");
            return self::FAILURE;
        }

        return $this->generateResource($modelClass);
    }

    protected function generateAll(): int
    {
        $this->info('Scanning for Eloquent models...');

        $models = $this->discoverModels();

        if (empty($models)) {
            $this->warn('No models found in app/Models directory');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($models) . ' model(s)');
        $this->newLine();

        $generated = 0;
        foreach ($models as $modelClass) {
            if ($this->generateResource($modelClass) === self::SUCCESS) {
                $generated++;
            }
        }

        $this->newLine();
        $this->info("Generated {$generated} resource(s) successfully!");

        return self::SUCCESS;
    }

    protected function generateResource(string $modelClass): int
    {
        $model = new $modelClass();
        $table = $model->getTable();
        $connection = $model->getConnection();

        // Get table columns
        $columns = $connection->getSchemaBuilder()->getColumnListing($table);

        if (empty($columns)) {
            $this->warn("No columns found for model [{$modelClass}]");
            return self::FAILURE;
        }

        // Generate resource
        $resourceName = class_basename($modelClass);
        $resourceDir = app_path("Ave/Resources/{$resourceName}");
        $resourcePath = "{$resourceDir}/Resource.php";

        if (File::exists($resourcePath) && !$this->option('force')) {
            $this->warn("Resource already exists: {$resourcePath}");
            $this->line('Use --force to overwrite');
            return self::FAILURE;
        }

        // Ensure directory exists
        File::ensureDirectoryExists($resourceDir);

        // Generate content
        $content = $this->generateResourceContent($modelClass, $resourceName, $columns, $model);

        File::put($resourcePath, $content);

        $this->info("✓ Generated: {$resourcePath}");

        // Create menu item
        $this->createMenuItem($resourceName);

        return self::SUCCESS;
    }

    protected function generateResourceContent(string $modelClass, string $resourceName, array $columns, Model $model): string
    {
        $slug = Str::plural(Str::kebab($resourceName));
        $label = Str::plural(Str::title(Str::snake($resourceName, ' ')));
        $singular = Str::singular($label);

        // Generate table columns
        $tableColumns = $this->generateTableColumns($columns);

        // Generate form fields
        $formFields = $this->generateFormFields($columns, $model);

        $template = $this->getStub();

        return str_replace(
            [
                '{{ namespace }}',
                '{{ modelClass }}',
                '{{ resourceName }}',
                '{{ slug }}',
                '{{ label }}',
                '{{ singular }}',
                '{{ tableColumns }}',
                '{{ formFields }}',
            ],
            [
                "App\\Ave\\Resources\\{$resourceName}",
                $modelClass,
                $resourceName,
                $slug,
                $label,
                $singular,
                $tableColumns,
                $formFields,
            ],
            $template
        );
    }

    /**
     * Get the stub file for resource generation.
     *
     * First checks for published stub in application, then falls back to package stub.
     */
    protected function getStub(): string
    {
        $publishedStub = base_path('stubs/ave/resource.stub');
        $packageStub = __DIR__ . '/../../../stubs/resource.stub';

        if (file_exists($publishedStub)) {
            return file_get_contents($publishedStub);
        }

        return file_get_contents($packageStub);
    }

    protected function generateTableColumns(array $columns): string
    {
        $output = [];
        $excludeFromTable = ['password', 'remember_token', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($columns as $column) {
            if (in_array($column, $excludeFromTable)) {
                continue;
            }

            $label = Str::title(str_replace('_', ' ', $column));
            $sortable = in_array($column, ['id', 'name', 'title', 'created_at']) ? '->sortable()' : '';
            $searchable = in_array($column, ['name', 'title', 'email']) ? '->searchable()' : '';

            $output[] = "                TextColumn::make('{$column}')\n                    ->label(__('{$label}')){$sortable}{$searchable},";
        }

        return implode("\n\n", $output);
    }

    protected function generateFormFields(array $columns, Model $model): string
    {
        $fields = [];
        $excludeFromForm = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'email_verified_at'];

        foreach ($columns as $column) {
            if (in_array($column, $excludeFromForm)) {
                continue;
            }

            $label = Str::title(str_replace('_', ' ', $column));
            $field = $this->guessFieldType($column, $model);

                $fields[] = $field;
        }

        return implode("\n\n", $fields);
    }

    protected function guessFieldType(string $column, Model $model): string
    {
        $label = Str::title(str_replace('_', ' ', $column));

        // Password fields
        if (Str::contains($column, 'password')) {
            return "                PasswordInput::make('{$column}')\n                    ->label(__('{$label}'))\n                    ->minLength(8),";
        }

        // Email fields
        if (Str::contains($column, 'email')) {
            return "                TextInput::make('{$column}')\n                    ->label(__('{$label}'))\n                    ->email()\n                    ->required(),";
        }

        // Text fields
        if (Str::endsWith($column, '_text') || Str::contains($column, 'description') || Str::contains($column, 'content') || Str::contains($column, 'body')) {
            return "                Textarea::make('{$column}')\n                    ->label(__('{$label}')),";
        }

        // Date/time fields
        if (Str::endsWith($column, '_at') || Str::contains($column, 'date')) {
            return "                DateTimePicker::make('{$column}')\n                    ->label(__('{$label}')),";
        }

        // Numeric fields
        $columnType = $model->getConnection()->getSchemaBuilder()->getColumnType($model->getTable(), $column);
        if (in_array($columnType, ['integer', 'bigint', 'smallint', 'decimal', 'float', 'double'])) {
            return "                Number::make('{$column}')\n                    ->label(__('{$label}')),";
        }

        // Default to TextInput
        return "                TextInput::make('{$column}')\n                    ->label(__('{$label}')),";
    }

    protected function discoverModels(): array
    {
        $modelsPath = app_path('Models');

        if (!File::isDirectory($modelsPath)) {
            return [];
        }

        $models = [];
        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);

            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Model::class)) {
                continue;
            }

            if (in_array($className, $this->excludeModels)) {
                continue;
            }

            $models[] = $className;
        }

        return $models;
    }

    protected function resolveModelClass(string $input): ?string
    {
        // Try as full class name
        if (class_exists($input)) {
            return $input;
        }

        // Try with App\Models namespace
        $withNamespace = "App\\Models\\{$input}";
        if (class_exists($withNamespace)) {
            return $withNamespace;
        }

        return null;
    }

    protected function createMenuItem(string $resourceName): void
    {
        $slug = Str::plural(Str::kebab($resourceName));
        $label = Str::plural(Str::title(Str::snake($resourceName, ' ')));

        // Get admin menu
        $menuId = \DB::table('ave_menus')->where('key', 'admin')->value('id');

        if (!$menuId) {
            $this->warn('Admin menu not found. Skipping menu item creation.');
            return;
        }

        // Check if menu item already exists
        $exists = \DB::table('ave_menu_items')
            ->where('menu_id', $menuId)
            ->where('resource_slug', $slug)
            ->exists();

        if ($exists) {
            $this->line("Menu item for '{$slug}' already exists");
            return;
        }

        // Get max order
        $maxOrder = \DB::table('ave_menu_items')
            ->where('menu_id', $menuId)
            ->max('order') ?? 0;

        // Insert menu item
        \DB::table('ave_menu_items')->insert([
            'menu_id' => $menuId,
            'title' => $label,
            'status' => true,
            'icon' => 'voyager-list',
            'resource_slug' => $slug,
            'ability' => 'viewAny',
            'order' => $maxOrder + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✓ Menu item created for '{$label}'");
    }
}
