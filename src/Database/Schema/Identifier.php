<?php

namespace Monstrex\Ave\Database\Schema;

use Illuminate\Support\Facades\Validator;

abstract class Identifier
{
    // Warning: Do not modify this regex
    public const REGEX = '^[a-zA-Z_][a-zA-Z0-9_]*$';

    // Maximum length for database identifiers (PostgreSQL: 63, MySQL: 64)
    // Using 63 as safe limit for all databases
    public const MAX_LENGTH = 63;

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
            'identifier' => [
                'required',
                'max:'.static::MAX_LENGTH,
                'regex:/'.static::REGEX.'/',
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->get('identifier');
            $message = "{$asset} Identifier '{$identifier}' is invalid. ";

            if (str_contains($errors[0], 'max')) {
                $message .= "Maximum length is " . static::MAX_LENGTH . " characters.";
            } else {
                $message .= "Must start with letter or underscore, contain only letters, numbers and underscores.";
            }

            throw new \Exception($message);
        }

        return $identifier;
    }
}
