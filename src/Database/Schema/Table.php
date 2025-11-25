<?php

namespace Monstrex\Ave\Database\Schema;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

class Table extends DoctrineTable
{
    public static function make(array|string $table): static
    {
        if (!is_array($table)) {
            $table = json_decode($table, true);
        }

        $name = Identifier::validate($table['name'], 'Table');

        $columns = [];
        foreach ($table['columns'] as $columnArr) {
            $column = Column::make($columnArr, $table['name']);
            $columns[$column->getName()] = $column;
        }

        $indexes = [];
        foreach ($table['indexes'] as $indexArr) {
            $index = Index::make($indexArr);
            $indexes[$index->getName()] = $index;
        }

        $foreignKeys = [];
        foreach ($table['foreignKeys'] as $foreignKeyArr) {
            $foreignKey = ForeignKey::make($foreignKeyArr);
            $foreignKeys[$foreignKey->getName()] = $foreignKey;
        }

        $options = $table['options'];

        return new self($name, $columns, $indexes, [], $foreignKeys, $options);
    }

    public function getColumnsIndexes($columns, bool $sort = false): array
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $matched = [];

        foreach ($this->_indexes as $index) {
            if ($index->spansColumns($columns)) {
                $matched[$index->getName()] = $index;
            }
        }

        if (count($matched) > 1 && $sort) {
            // Sort indexes based on priority: PRI > UNI > IND
            uasort($matched, function ($index1, $index2) {
                $index1_type = Index::getType($index1);
                $index2_type = Index::getType($index2);

                if ($index1_type == $index2_type) {
                    return 0;
                }

                if ($index1_type == Index::PRIMARY) {
                    return -1;
                }

                if ($index2_type == Index::PRIMARY) {
                    return 1;
                }

                if ($index1_type == Index::UNIQUE) {
                    return -1;
                }

                // If we reach here, it means: $index1=INDEX && $index2=UNIQUE
                return 1;
            });
        }

        return $matched;
    }

    public function diff(DoctrineTable $compareTable)
    {
        $comparator = new Comparator(SchemaManager::getDatabasePlatform());

        return $comparator->compareTables($this, $compareTable);
    }

    public function diffOriginal()
    {
        $comparator = new Comparator(SchemaManager::getDatabasePlatform());

        return $comparator->compareTables(SchemaManager::getDoctrineTable($this->_name), $this);
    }

    /**
     * Convert table to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'name'           => $this->_name,
            'oldName'        => $this->_name,
            'columns'        => $this->exportColumnsToArray(),
            'indexes'        => $this->exportIndexesToArray(),
            'primaryKeyName' => $this->_primaryKeyName,
            'foreignKeys'    => $this->exportForeignKeysToArray(),
            'options'        => $this->_options,
        ];
    }

    /**
     * Convert table to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Export columns to array
     */
    public function exportColumnsToArray(): array
    {
        $exportedColumns = [];

        foreach ($this->getColumns() as $name => $column) {
            $columnArr = Column::toArray($column);

            // Add field (alias for name)
            $columnArr['field'] = $columnArr['name'];

            // Set the indexes and key
            $columnArr['indexes'] = [];
            $columnArr['key'] = null;
            $columnArr['index'] = null;
            if ($columnArr['indexes'] = $this->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                foreach ($columnArr['indexes'] as $indexName => $index) {
                    $columnArr['indexes'][$indexName] = Index::toArray($index);
                }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
                $indexType = array_values($columnArr['indexes'])[0]['type'];
                $columnArr['key'] = substr($indexType, 0, 3);
                // Set full index type for JavaScript (for select dropdown)
                $columnArr['index'] = $indexType;
            }

            $exportedColumns[] = $columnArr;
        }

        return $exportedColumns;
    }

    /**
     * Export indexes to array
     */
    public function exportIndexesToArray(): array
    {
        $exportedIndexes = [];

        foreach ($this->getIndexes() as $name => $index) {
            $indexArr = Index::toArray($index);
            $indexArr['table'] = $this->_name;
            $exportedIndexes[] = $indexArr;
        }

        return $exportedIndexes;
    }

    /**
     * Export foreign keys to array
     */
    public function exportForeignKeysToArray(): array
    {
        $exportedForeignKeys = [];

        foreach ($this->getForeignKeys() as $name => $fk) {
            $exportedForeignKeys[$name] = ForeignKey::toArray($fk, $this->_name);
        }

        return $exportedForeignKeys;
    }

    public function __get($property)
    {
        $getter = 'get'.ucfirst($property);

        if (!method_exists($this, $getter)) {
            throw new \Exception("Property {$property} doesn't exist or is unavailable");
        }

        return $this->$getter();
    }
}
