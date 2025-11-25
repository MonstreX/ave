<?php

namespace Monstrex\Ave\Database\Schema;

use Illuminate\Support\Facades\Validator;

abstract class Identifier
{
    // Warning: Do not modify this regex
    public const REGEX = '^[a-zA-Z_][a-zA-Z0-9_]*$';

    /**
     * Validate database identifier (table name, column name, index name, etc.)
     *
     * @param string $identifier The identifier to validate
     * @param string $asset Asset type for error message (e.g., "Table", "Column")
     * @return string The validated and trimmed identifier
     * @throws \Exception If identifier is invalid
     */
    public static function validate(string $identifier, string $asset = ''): string
    {
        $identifier = trim($identifier);

        $validator = Validator::make(['identifier' => $identifier], [
            'identifier' => 'required|regex:/'.static::REGEX.'/',
        ]);

        if ($validator->fails()) {
            throw new \Exception("{$asset} Identifier '{$identifier}' is invalid. Must start with letter or underscore, contain only letters, numbers and underscores.");
        }

        return $identifier;
    }
}
