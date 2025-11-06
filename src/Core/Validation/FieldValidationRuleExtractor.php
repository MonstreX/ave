<?php

namespace Monstrex\Ave\Core\Validation;

use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Textarea;

/**
 * Extracts field-specific validation rules based on field type.
 *
 * Converts field attributes (minLength, maxLength, pattern, min, max) to Laravel rules.
 * This class is used by both FormValidator and Fieldset to ensure consistent validation rules.
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
        // Handle TextInput: minLength, maxLength, pattern
        if ($field instanceof TextInput) {
            return self::extractTextInputRules($field, $baseRules);
        }

        // Handle Number: min, max
        if ($field instanceof Number) {
            return self::extractNumberRules($field, $baseRules);
        }

        // Handle Textarea: maxLength
        if ($field instanceof Textarea) {
            return self::extractTextareaRules($field, $baseRules);
        }

        return $baseRules;
    }

    /**
     * Extract TextInput validation rules.
     *
     * @param TextInput $field
     * @param array<int,string> $baseRules
     * @return array<int,string>
     */
    private static function extractTextInputRules(TextInput $field, array $baseRules): array
    {
        $reflection = new \ReflectionClass($field);

        // Get minLength
        if ($reflection->hasProperty('minLength')) {
            $minProperty = $reflection->getProperty('minLength');
            $minProperty->setAccessible(true);
            $minLength = $minProperty->getValue($field);
            if ($minLength !== null) {
                $baseRules[] = "min:{$minLength}";
            }
        }

        // Get maxLength
        if ($reflection->hasProperty('maxLength')) {
            $maxProperty = $reflection->getProperty('maxLength');
            $maxProperty->setAccessible(true);
            $maxLength = $maxProperty->getValue($field);
            if ($maxLength !== null) {
                $baseRules[] = "max:{$maxLength}";
            }
        }

        // Get pattern - convert to regex rule
        if ($reflection->hasProperty('pattern')) {
            $patternProperty = $reflection->getProperty('pattern');
            $patternProperty->setAccessible(true);
            $pattern = $patternProperty->getValue($field);
            if ($pattern !== null) {
                $baseRules[] = "regex:/{$pattern}/";
            }
        }

        return $baseRules;
    }

    /**
     * Extract Number validation rules.
     *
     * @param Number $field
     * @param array<int,string> $baseRules
     * @return array<int,string>
     */
    private static function extractNumberRules(Number $field, array $baseRules): array
    {
        $reflection = new \ReflectionClass($field);

        // Get min
        if ($reflection->hasProperty('min')) {
            $minProperty = $reflection->getProperty('min');
            $minProperty->setAccessible(true);
            $min = $minProperty->getValue($field);
            if ($min !== null) {
                $baseRules[] = "min:{$min}";
            }
        }

        // Get max
        if ($reflection->hasProperty('max')) {
            $maxProperty = $reflection->getProperty('max');
            $maxProperty->setAccessible(true);
            $max = $maxProperty->getValue($field);
            if ($max !== null) {
                $baseRules[] = "max:{$max}";
            }
        }

        return $baseRules;
    }

    /**
     * Extract Textarea validation rules.
     *
     * @param Textarea $field
     * @param array<int,string> $baseRules
     * @return array<int,string>
     */
    private static function extractTextareaRules(Textarea $field, array $baseRules): array
    {
        $reflection = new \ReflectionClass($field);

        // Get maxLength
        if ($reflection->hasProperty('maxLength')) {
            $maxProperty = $reflection->getProperty('maxLength');
            $maxProperty->setAccessible(true);
            $maxLength = $maxProperty->getValue($field);
            if ($maxLength !== null) {
                $baseRules[] = "max:{$maxLength}";
            }
        }

        return $baseRules;
    }
}
