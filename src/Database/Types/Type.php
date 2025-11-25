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
                ['name' => 'integer', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'bigint', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'smallint', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'decimal', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'float', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'double', 'notSupported' => false, 'notSupportIndex' => false],
            ],
            'Strings' => [
                ['name' => 'string', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'text', 'notSupported' => false, 'notSupportIndex' => true],
                ['name' => 'char', 'notSupported' => false, 'notSupportIndex' => false],
            ],
            'Date & Time' => [
                ['name' => 'date', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'datetime', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'time', 'notSupported' => false, 'notSupportIndex' => false],
            ],
            'Other' => [
                ['name' => 'boolean', 'notSupported' => false, 'notSupportIndex' => false],
                ['name' => 'json', 'notSupported' => false, 'notSupportIndex' => true],
                ['name' => 'binary', 'notSupported' => false, 'notSupportIndex' => true],
                ['name' => 'blob', 'notSupported' => false, 'notSupportIndex' => true],
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
