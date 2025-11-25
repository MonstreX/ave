<?php

namespace Monstrex\Ave\Database\Schema;

use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Monstrex\Ave\Database\DoctrineManager;
use Monstrex\Ave\Database\Types\Type;

abstract class SchemaManager
{
    public static function __callStatic($method, $args)
    {
        return static::manager()->$method(...$args);
    }

    public static function manager()
    {
        return DoctrineManager::schemaManager();
    }

    public static function getDatabaseConnection()
    {
        return DoctrineManager::connection();
    }

    public static function getDatabasePlatform()
    {
        return DoctrineManager::connection()->getDatabasePlatform();
    }

    public static function tableExists($table): bool
    {
        if (!is_array($table)) {
            $table = [$table];
        }

        return static::manager()->tablesExist($table);
    }

    public static function listTables(): array
    {
        $tables = [];

        foreach (static::manager()->listTableNames() as $tableName) {
            $tables[$tableName] = static::listTableDetails($tableName);
        }

        return $tables;
    }

    public static function listTableNames(): array
    {
        return static::manager()->listTableNames();
    }

    /**
     * Get detailed information about a table
     *
     * @param string $tableName
     * @return \Monstrex\Ave\Database\Schema\Table
     */
    public static function listTableDetails(string $tableName): Table
    {
        $columns = static::manager()->listTableColumns($tableName);

        $platform = static::getDatabasePlatform();
        $foreignKeys = [];
        if (static::platformSupportsForeignKeys($platform)) {
            $foreignKeys = static::manager()->listTableForeignKeys($tableName);
        }

        $indexes = static::manager()->listTableIndexes($tableName);

        return new Table($tableName, $columns, $indexes, [], $foreignKeys, []);
    }

    /**
     * Describe given table in a format suitable for display
     *
     * @param string $tableName
     * @return \Illuminate\Support\Collection
     */
    public static function describeTable(string $tableName)
    {
        Type::registerCustomPlatformTypes();

        $table = static::listTableDetails($tableName);

        return collect($table->columns)->map(function ($column) use ($table) {
            $columnArr = Column::toArray($column);

            $columnArr['field'] = $columnArr['name'];
            $columnArr['type'] = $columnArr['type']['name'];

            // Set the indexes and key
            $columnArr['indexes'] = [];
            $columnArr['key'] = null;
            if ($columnArr['indexes'] = $table->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                foreach ($columnArr['indexes'] as $name => $index) {
                    $columnArr['indexes'][$name] = Index::toArray($index);
                }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
                $indexType = array_values($columnArr['indexes'])[0]['type'];
                $columnArr['key'] = substr($indexType, 0, 3);
            }

            return $columnArr;
        });
    }

    public static function listTableColumnNames(string $tableName): array
    {
        Type::registerCustomPlatformTypes();

        $columnNames = [];

        foreach (static::manager()->listTableColumns($tableName) as $column) {
            $columnNames[] = $column->getName();
        }

        return $columnNames;
    }

    public static function createTable($table): void
    {
        if (!($table instanceof DoctrineTable)) {
            $table = Table::make($table);
        }

        static::manager()->createTable($table);
    }

    public static function getDoctrineTable(string $table): DoctrineTable
    {
        $table = trim($table);

        if (!static::tableExists($table)) {
            throw TableDoesNotExist::new($table);
        }

        return static::getDatabaseConnection()
            ->createSchemaManager()
            ->introspectTable($table);
    }

    public static function getDoctrineColumn(string $table, string $column)
    {
        return static::getDoctrineTable($table)->getColumn($column);
    }

    protected static function platformSupportsForeignKeys($platform): bool
    {
        if (method_exists($platform, 'supportsForeignKeyConstraints')) {
            return $platform->supportsForeignKeyConstraints();
        }

        return Type::getPlatformName($platform) !== 'sqlite';
    }
}
