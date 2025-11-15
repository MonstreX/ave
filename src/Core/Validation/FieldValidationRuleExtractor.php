<?php

namespace Monstrex\Ave\Core\Validation;

use Monstrex\Ave\Contracts\ProvidesValidationAttributes;
use Monstrex\Ave\Core\Fields\AbstractField;

/**
 * Extracts field-specific validation rules based on field type.
 *
 * Converts field attributes (minLength, maxLength, pattern, min, max) to Laravel rules.
 * This class is used by both FormValidator and Fieldset to ensure consistent validation rules.
 *
 * Uses ProvidesValidationAttributes interface to get field properties without Reflection API.
 */
class FieldValidationRuleExtractor
{
    /**
     * Extract field-specific validation rules.
     *
     * @param AbstractField $field
     * @param array<int,string> $baseRules
     * @return array<int,string>
     */
    public static function extract(AbstractField $field, array $baseRules): array
    {
        // Use interface if field implements it (preferred method - no Reflection)
        if ($field instanceof ProvidesValidationAttributes) {
            return self::extractFromInterface($field, $baseRules);
        }

        // Field doesn't implement interface - return base rules unchanged
        return $baseRules;
    }

    /**
     * Extract validation rules using ProvidesValidationAttributes interface.
     *
     * @param ProvidesValidationAttributes $field
     * @param array<int,string> $baseRules
     * @return array<int,string>
     */
    private static function extractFromInterface(ProvidesValidationAttributes $field, array $baseRules): array
    {
        $attrs = $field->getValidationAttributes();

        // Add min_length rule if specified
        if (isset($attrs['min_length']) && $attrs['min_length'] !== null) {
            $baseRules[] = "min:{$attrs['min_length']}";
        }

        // Add max_length rule if specified
        if (isset($attrs['max_length']) && $attrs['max_length'] !== null) {
            $baseRules[] = "max:{$attrs['max_length']}";
        }

        // Add regex pattern rule if specified
        if (isset($attrs['pattern']) && $attrs['pattern'] !== null) {
            $baseRules[] = "regex:/{$attrs['pattern']}/";
        }

        // Add min rule for numeric fields
        if (isset($attrs['min']) && $attrs['min'] !== null) {
            $baseRules[] = "min:{$attrs['min']}";
        }

        // Add max rule for numeric fields
        if (isset($attrs['max']) && $attrs['max'] !== null) {
            $baseRules[] = "max:{$attrs['max']}";
        }

        return $baseRules;
    }
}
