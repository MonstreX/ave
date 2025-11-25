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

        try {
            $conn = 'database.connections.'.config('database.default');
            Type::registerCustomPlatformTypes();

            $table = $request->table;
            if (!is_array($request->table)) {
                $table = json_decode($request->table, true);
            }

            $table['options']['collate'] = config($conn.'.collation', 'utf8mb4_unicode_ci');
            $table['options']['charset'] = config($conn.'.charset', 'utf8mb4');

            $table = Table::make($table);
            SchemaManager::createTable($table);

            // TODO: Dispatch TableAdded event
            // event(new TableAdded($table));

            return redirect()
                ->route('ave.database.index')
                ->with('success', __('ave::database.success_create_table', ['table' => $table->getName()]));
        } catch (Exception $e) {
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
            DatabaseUpdater::update($tableData);

            // TODO: Dispatch TableUpdated event
            // event(new TableUpdated($tableData));

            return redirect()
                ->route('ave.database.index')
                ->with('success', __('ave::database.success_update_table', ['table' => $tableData['name']]));
        } catch (Exception $e) {
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
                ->with('success', __('ave::database.success_delete_table', ['table' => $table]));
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
}
