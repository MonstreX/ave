<?php

namespace Monstrex\Ave\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Monstrex\Ave\Database\DatabaseUpdater;
use Monstrex\Ave\Database\Schema\Identifier;
use Monstrex\Ave\Database\Schema\SchemaManager;
use Monstrex\Ave\Database\Schema\Table;
use Monstrex\Ave\Database\Types\Type;

class DatabaseController extends Controller
{
    /**
     * Display list of database tables
     */
    public function index()
    {
        // TODO: Add authorization check
        // $this->authorize('browse_database');

        $hiddenTables = config('ave.database.hidden_tables', []);

        $tables = collect(SchemaManager::listTableNames())
            ->reject(fn($table) => in_array($table, $hiddenTables))
            ->map(function ($table) {
                $table = Str::replaceFirst(DB::getTablePrefix(), '', $table);

                return (object) [
                    'prefix' => DB::getTablePrefix(),
                    'name' => $table,
                ];
            })
            ->values()
            ->all();

        return view('ave::database.index', compact('tables'));
    }

    /**
     * Show form to create new table
     */
    public function create()
    {
        // TODO: Add authorization check
        // $this->authorize('add_database');

        $db = $this->prepareDbManager('create');

        return view('ave::database.edit-add', compact('db'));
    }

    /**
     * Store new table in database
     */
    public function store(Request $request)
    {
        // TODO: Add authorization check
        // $this->authorize('add_database');

        \Log::info('=== DATABASE STORE REQUEST ===');
        \Log::info('Request data:', [
            'table' => $request->table ? substr($request->table, 0, 200) : 'null',
            'create_model' => $request->create_model,
            'model_name' => $request->model_name,
        ]);

        try {
            $conn = 'database.connections.'.config('database.default');
            Type::registerCustomPlatformTypes();

            $table = $request->table;
            if (!is_array($request->table)) {
                $table = json_decode($request->table, true);
            }

            \Log::info('Decoded table data:', [
                'name' => $table['name'] ?? 'null',
                'columns_count' => count($table['columns'] ?? []),
                'indexes_count' => count($table['indexes'] ?? [])
            ]);

            $table['options']['collate'] = config($conn.'.collation', 'utf8mb4_unicode_ci');
            $table['options']['charset'] = config($conn.'.charset', 'utf8mb4');

            \Log::info('STEP 1: Before Table::make');
            $tableObj = Table::make($table);
            \Log::info('STEP 2: After Table::make, table name: ' . $tableObj->getName());

            \Log::info('STEP 3: Before SchemaManager::createTable');
            SchemaManager::createTable($tableObj);
            \Log::info('STEP 4: After createTable - SUCCESS');

            // Create model if requested
            if ($request->filled('create_model') && $request->filled('model_name')) {
                try {
                    \Log::info('STEP 5: Creating model: ' . $request->model_name);
                    $this->createModel($request->model_name, $tableObj->getName());
                    \Log::info('STEP 6: Model created successfully');
                } catch (\Exception $e) {
                    \Log::error('Model creation failed: ' . $e->getMessage());
                    \Log::error('Stack trace: ' . $e->getTraceAsString());
                    // Don't fail the whole operation if model creation fails
                }
            }

            // TODO: Dispatch TableAdded event
            // event(new TableAdded($table));

            \Log::info('STEP 7: Redirecting to database.index');
            return redirect()
                ->route('ave.database.index')
                ->with('success', __('ave::database.success_create_table', ['table' => $tableObj->getName()]));
        } catch (Exception $e) {
            \Log::error('DATABASE STORE EXCEPTION: ' . $e->getMessage());
            \Log::error('Exception class: ' . get_class($e));
            \Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form to edit existing table
     */
    public function edit(string $table)
    {
        // TODO: Add authorization check
        // $this->authorize('edit_database');

        if (!SchemaManager::tableExists($table)) {
            return redirect()
                ->route('ave.database.index')
                ->with('error', __('ave::database.edit_table_not_exist'));
        }

        $db = $this->prepareDbManager('update', $table);

        return view('ave::database.edit-add', compact('db'));
    }

    /**
     * Update existing table structure
     */
    public function update(Request $request, string $table)
    {
        // TODO: Add authorization check
        // $this->authorize('edit_database');

        try {
            $tableData = json_decode($request->table, true);

            \Log::info('=== DATABASE UPDATE REQUEST ===');
            \Log::info('Table name: ' . $table);
            \Log::info('Request table field length: ' . strlen($request->table));
            \Log::info('Decoded columns count: ' . count($tableData['columns'] ?? []));
            if (isset($tableData['columns'])) {
                foreach ($tableData['columns'] as $col) {
                    \Log::info('Column: ' . $col['name'], [
                        'index' => $col['index'] ?? 'not set',
                        'key' => $col['key'] ?? 'not set'
                    ]);
                }
            }

            DatabaseUpdater::update($tableData);

            // TODO: Dispatch TableUpdated event
            // event(new TableUpdated($tableData));

            return redirect()
                ->route('ave.database.index')
                ->with('success', __('ave::database.success_update_table', ['table' => $tableData['name']]));
        } catch (Exception $e) {
            \Log::error('Database update failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get table structure as JSON (for view modal)
     */
    public function show(string $table)
    {
        // TODO: Add authorization check
        // $this->authorize('browse_database');

        return response()->json(SchemaManager::describeTable($table));
    }

    /**
     * Delete table from database
     */
    public function destroy(string $table)
    {
        // TODO: Add authorization check
        // $this->authorize('delete_database');

        try {
            SchemaManager::dropTable($table);

            // TODO: Dispatch TableDeleted event
            // event(new TableDeleted($table));

            return redirect()
                ->route('ave.database.index')
                ->with('toast', [
                    'type' => 'success',
                    'message' => __('ave::database.success_delete_table', ['table' => $table])
                ]);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Prepare data for create/edit forms
     */
    protected function prepareDbManager(string $action, string $table = ''): object
    {
        $db = new \stdClass();

        // Get all available types grouped by category
        $db->types = Type::getPlatformTypes();

        if ($action === 'update') {
            $db->table = SchemaManager::listTableDetails($table);
            $db->formAction = route('ave.database.update', $table);
        } else {
            // Create new table with default id column
            $db->table = new Table('new_table');
            $db->table->addColumn('id', 'integer', [
                'unsigned' => true,
                'notnull' => true,
                'autoincrement' => true,
            ]);
            $db->table->setPrimaryKey(['id'], 'primary');

            $db->formAction = route('ave.database.store');
        }

        $db->oldTable = old('table') ? json_decode(old('table'), true) : null;
        $db->action = $action;
        $db->identifierRegex = Identifier::REGEX;
        $db->platform = Type::getPlatformName(SchemaManager::getDatabasePlatform());

        return $db;
    }

    /**
     * Create a new Eloquent model file
     */
    protected function createModel(string $fullClassName, string $tableName): void
    {
        // Parse namespace and class name
        $parts = explode('\\', $fullClassName);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);

        // Determine file path
        $basePath = app_path();
        $relativePath = str_replace('App\\', '', $namespace);
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
        $directory = $basePath . DIRECTORY_SEPARATOR . $relativePath;
        $filePath = $directory . DIRECTORY_SEPARATOR . $className . '.php';

        // Check if file already exists
        if (file_exists($filePath)) {
            throw new \Exception("Model file already exists: {$filePath}");
        }

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate model content
        $stub = $this->getModelStub();
        $content = str_replace(
            ['DummyNamespace', 'DummyClass', '//DummySDTraitInclude', '//DummySDTrait'],
            [$namespace, $className, '', ''],
            $stub
        );

        // Write file
        file_put_contents($filePath, $content);
    }

    /**
     * Get the model stub template
     */
    protected function getModelStub(): string
    {
        $stubPath = __DIR__ . '/../../../stubs/model.stub';

        if (!file_exists($stubPath)) {
            throw new \Exception("Model stub not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }
}
