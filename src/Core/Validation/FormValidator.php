<?php

namespace Monstrex\Ave\Core\Validation;

use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Fields\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Validates form data using field rules
 *
 * Builds validation rules from form fields with support for nested Fieldsets
 */
class FormValidator
{
    /**
     * Build validation rules from form
     *
     * Iterates through all form rows/columns/fields and builds validation rules.
     * Properly handles nested Fieldsets with dot notation.
     * Supports mode-specific rules (create vs edit).
     *
     * @param Form $form Form instance
     * @param string $resourceClass Resource class name
     * @param Request $request Current request
     * @param string $mode Create or edit mode
     * @param Model|null $model Model instance for edit mode
     * @return array Validation rules
     */
    public function rulesFromForm(Form $form, string $resourceClass, Request $request, string $mode = 'create', ?Model $model = null): array
    {
        $rules = [];

        // Iterate through all form rows
        foreach ($form->rows() as $row) {
            // Each row has columns
            foreach ($row['columns'] as $col) {
                // Each column has fields
                foreach ($col['fields'] as $field) {
                    $arr = $field->toArray();
                    $key = $arr['key'];
                    $fieldRules = $arr['rules'] ?? [];

                    // Handle Fieldset fields specially
                    if ($field instanceof Fieldset) {
                        // Process nested fields with dot notation
                        foreach ($field->getFields() as $nestedField) {
                            $nestedArr = $nestedField->toArray();
                            $nestedKey = $nestedArr['key'];
                            $nestedRules = $nestedArr['rules'] ?? [];

                            if (!empty($nestedRules)) {
                                // Use dot notation for nested validation: fieldset.*.nestedkey
                                $rules["{$key}.*.{$nestedKey}"] = $this->formatRules($nestedRules, $nestedField->isRequired());
                            }
                        }
                        continue;
                    }

                    // For normal fields, add rules
                    if (!empty($fieldRules)) {
                        $rules[$key] = $this->formatRules($fieldRules, $field->isRequired());
                    }
                }
            }
        }

        // Adjust unique rules for edit mode
        if ($mode === 'edit' && $model) {
            $rules = $this->adjustUniqueRulesForEdit($rules, $model);
        }

        return $rules;
    }

    /**
     * Format rules array or string to pipe-separated string
     *
     * @param array|string $rules Rules from field
     * @param bool $required Whether field is required
     * @return string Pipe-separated rules
     */
    protected function formatRules($rules, bool $required = false): string
    {
        // Convert to array if string
        if (is_string($rules)) {
            $rules = array_filter(explode('|', $rules));
        } else {
            $rules = array_filter((array) $rules);
        }

        // Add required/nullable rule
        if ($required) {
            if (!in_array('required', $rules)) {
                array_unshift($rules, 'required');
            }
        } else {
            if (!in_array('nullable', $rules)) {
                array_unshift($rules, 'nullable');
            }
        }

        return implode('|', $rules);
    }

    /**
     * Adjust unique rules for edit mode (exclude current model)
     *
     * @param array $rules Validation rules
     * @param Model $model Current model being edited
     * @return array Modified rules
     */
    protected function adjustUniqueRulesForEdit(array $rules, Model $model): array
    {
        foreach ($rules as $field => &$fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            foreach ($fieldRules as &$rule) {
                if (str_starts_with($rule, 'unique:')) {
                    // Add ignore for current model ID
                    $rule .= ',' . $model->getKey() . ',' . $model->getKeyName();
                }
            }

            $fieldRules = implode('|', $fieldRules);
        }

        return $rules;
    }
}
