<?php

namespace Monstrex\Ave\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DoctrineType;

abstract class Type
{
    /**
     * Type cache to avoid re-registration
     */
    protected static bool $typesRegistered = false;

    /**
     * Register all custom platform types with Doctrine
     */
    public static function registerCustomPlatformTypes(): void
    {
        if (static::$typesRegistered) {
            return;
        }

        // TODO: Register custom types here when we port them
        // For now, we'll use Doctrine's default types

        static::$typesRegistered = true;
    }

    /**
     * Flush type cache (used when forgetting connections)
     */
    public static function flushCache(): void
    {
        static::$typesRegistered = false;
    }

    /**
     * Get all available types grouped by category for UI
     */
    public static function getPlatformTypes(): array
    {
        // Basic types for now - we'll expand this as we port custom types
        return [
            'Numbers' => [
                ['name' => 'integer', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'bigint', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'smallint', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'decimal', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => true, 'defaultLength' => '10,2'],
                ['name' => 'float', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'double', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
            ],
            'Strings' => [
                ['name' => 'string', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => true, 'defaultLength' => '255'],
                ['name' => 'text', 'supported' => true, 'notSupportIndex' => true, 'requiresLength' => false],
                ['name' => 'char', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => true, 'defaultLength' => '1'],
            ],
            'Date & Time' => [
                ['name' => 'date', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'datetime', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'time', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
            ],
            'Other' => [
                ['name' => 'boolean', 'supported' => true, 'notSupportIndex' => false, 'requiresLength' => false],
                ['name' => 'json', 'supported' => true, 'notSupportIndex' => true, 'requiresLength' => false],
                ['name' => 'binary', 'supported' => true, 'notSupportIndex' => true, 'requiresLength' => false],
                ['name' => 'blob', 'supported' => true, 'notSupportIndex' => true, 'requiresLength' => false],
            ],
        ];
    }

    /**
     * Resolve Doctrine column type from string name
     */
    public static function resolveDoctrineColumnType(string $typeName): DoctrineType
    {
        $typeName = strtolower(trim($typeName));

        // Map common type aliases
        $typeMap = [
            'int' => 'integer',
            'varchar' => 'string',
            'timestamp' => 'datetime',
        ];

        $typeName = $typeMap[$typeName] ?? $typeName;

        try {
            return DoctrineType::getType($typeName);
        } catch (\Exception $e) {
            // Fallback to string type if unknown
            return DoctrineType::getType('string');
        }
    }

    /**
     * Convert Doctrine type to array representation
     */
    public static function toArray(DoctrineType $type): array
    {
        // In DBAL 4.x, getName() doesn't exist, use lookupName() instead
        $typeName = DoctrineType::lookupName($type);

        return [
            'name' => $typeName,
            'notSupported' => false,
            'notSupportIndex' => in_array($typeName, ['text', 'json', 'binary', 'blob']),
        ];
    }

    /**
     * Get type label for display
     */
    public static function getTypeLabel(DoctrineType $type): string
    {
        return DoctrineType::lookupName($type);
    }

    /**
     * Get platform name (mysql, postgresql, sqlite, etc.)
     */
    public static function getPlatformName(AbstractPlatform $platform): string
    {
        $class = get_class($platform);

        if (str_contains($class, 'MySQL')) {
            return 'mysql';
        }
        if (str_contains($class, 'PostgreSQL') || str_contains($class, 'Postgre')) {
            return 'postgresql';
        }
        if (str_contains($class, 'SQLite')) {
            return 'sqlite';
        }
        if (str_contains($class, 'SQLServer')) {
            return 'sqlserver';
        }

        return 'unknown';
    }
}
