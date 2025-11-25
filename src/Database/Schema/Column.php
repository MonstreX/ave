<?php

namespace Monstrex\Ave\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Monstrex\Ave\Database\Types\Type;

abstract class Column
{
    public static function make(array $column, ?string $tableName = null): DoctrineColumn
    {
        $name = Identifier::validate($column['name'], 'Column');
        $type = $column['type'];

        if (!($type instanceof DoctrineType)) {
            $typeName = is_array($type) ? ($type['name'] ?? '') : (string) $type;
            $type = Type::resolveDoctrineColumnType($typeName);
        }

        $typeLabel = strtolower(Type::getTypeLabel($type));
        $numericAutoIncrement = ['tinyint', 'smallint', 'mediumint', 'integer', 'int', 'bigint'];
        $numericUnsigned = array_merge($numericAutoIncrement, ['decimal', 'numeric', 'float', 'double', 'double precision', 'real']);

        if (!in_array($typeLabel, $numericAutoIncrement, true)) {
            $column['autoincrement'] = false;
        }

        if (!in_array($typeLabel, $numericUnsigned, true)) {
            $column['unsigned'] = false;
        }

        $type->tableName = $tableName;

        $lengthRequired = ['varchar', 'nvarchar', 'varchar2', 'bpchar', 'string'];
        if (in_array($typeLabel, $lengthRequired, true) && empty($column['length'])) {
            $column['length'] = 191;
        }

        // Convert string values to proper types for Doctrine
        if (isset($column['length']) && $column['length'] !== null && $column['length'] !== '') {
            $column['length'] = (int) $column['length'];
        } else {
            $column['length'] = null;
        }

        if (isset($column['precision']) && $column['precision'] !== null && $column['precision'] !== '') {
            $column['precision'] = (int) $column['precision'];
        } else {
            $column['precision'] = null;
        }

        if (isset($column['scale']) && $column['scale'] !== null && $column['scale'] !== '') {
            $column['scale'] = (int) $column['scale'];
        } else {
            $column['scale'] = 0;
        }

        $options = array_diff_key($column, array_flip(['name', 'composite', 'oldName', 'null', 'extra', 'type', 'charset', 'collation', 'field', 'key', 'index', 'indexes']));

        return new DoctrineColumn($name, $type, $options);
    }

    /**
     * Convert Doctrine Column to array
     */
    public static function toArray(DoctrineColumn $column): array
    {
        $columnArr = $column->toArray();
        $columnArr['type'] = Type::toArray($columnArr['type']);
        $columnArr['oldName'] = $columnArr['name'];
        $columnArr['null'] = $columnArr['notnull'] ? 'NO' : 'YES';
        $columnArr['extra'] = static::getExtra($column);
        $columnArr['composite'] = false;

        return $columnArr;
    }

    /**
     * Get extra column information (like auto_increment)
     */
    protected static function getExtra(DoctrineColumn $column): string
    {
        $extra = '';

        $extra .= $column->getAutoincrement() ? 'auto_increment' : '';
        // TODO: Add Extra stuff like mysql 'onUpdate' etc...

        return $extra;
    }
}
