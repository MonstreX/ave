<?php

namespace Monstrex\Ave\Database\Schema;

use Doctrine\DBAL\Schema\Index as DoctrineIndex;

abstract class Index
{
    public const PRIMARY = 'PRIMARY';
    public const UNIQUE = 'UNIQUE';
    public const INDEX = 'INDEX';

    public static function make(array $index): DoctrineIndex
    {
        $columns = $index['columns'];
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (isset($index['type'])) {
            $type = $index['type'];

            $isPrimary = ($type == static::PRIMARY);
            $isUnique = $isPrimary || ($type == static::UNIQUE);
        } else {
            $isPrimary = $index['isPrimary'];
            $isUnique = $index['isUnique'];

            // Set the type
            if ($isPrimary) {
                $type = static::PRIMARY;
            } elseif ($isUnique) {
                $type = static::UNIQUE;
            } else {
                $type = static::INDEX;
            }
        }

        // Set the name
        $name = trim($index['name'] ?? '');
        if (empty($name)) {
            $table = $index['table'] ?? null;
            $name = static::createName($columns, $type, $table);
        } else {
            $name = Identifier::validate($name, 'Index');
        }

        $flags = $index['flags'] ?? [];
        $options = $index['options'] ?? [];

        return new DoctrineIndex($name, $columns, $isUnique, $isPrimary, $flags, $options);
    }

    /**
     * Convert Doctrine Index to array
     */
    public static function toArray(DoctrineIndex $index): array
    {
        $name = $index->getName();
        $columns = $index->getColumns();

        return [
            'name'        => $name,
            'oldName'     => $name,
            'columns'     => $columns,
            'type'        => static::getType($index),
            'isPrimary'   => $index->isPrimary(),
            'isUnique'    => $index->isUnique(),
            'isComposite' => count($columns) > 1,
            'flags'       => $index->getFlags(),
            'options'     => $index->getOptions(),
        ];
    }

    public static function getType(DoctrineIndex $index): string
    {
        if ($index->isPrimary()) {
            return static::PRIMARY;
        } elseif ($index->isUnique()) {
            return static::UNIQUE;
        } else {
            return static::INDEX;
        }
    }

    /**
     * Create a default index name.
     *
     * @param array  $columns Column names
     * @param string $type Index type (PRIMARY, UNIQUE, INDEX)
     * @param string|null $table Table name
     * @return string Generated index name
     */
    public static function createName(array $columns, string $type, ?string $table = null): string
    {
        $table = isset($table) ? trim($table).'_' : '';
        $type = trim($type);
        $name = strtolower($table.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $name);
    }

    public static function availableTypes(): array
    {
        return [
            static::PRIMARY,
            static::UNIQUE,
            static::INDEX,
        ];
    }
}
