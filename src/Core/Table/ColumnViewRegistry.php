<?php

namespace Monstrex\Ave\Core\Table;

class ColumnViewRegistry
{
    /**
     * @var array<string,string>
     */
    protected static array $views = [
        'text' => 'ave::components.tables.text-column',
        'date' => 'ave::components.tables.date-column',
        'boolean' => 'ave::components.tables.boolean-column',
        'badge' => 'ave::components.tables.badge-column',
        'image' => 'ave::components.tables.image-column',
        'template' => 'ave::components.tables.template-column',
    ];

    public static function register(string $type, string $view): void
    {
        static::$views[$type] = $view;
    }

    public static function resolve(string $type): string
    {
        return static::$views[$type] ?? static::$views['text'];
    }
}

