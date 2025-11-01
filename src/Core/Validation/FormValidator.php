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
     * Supports mode-specific rules (create vs edit) and field nesting
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

        foreach ($form->getAllFields() as $field) {
            $fieldRules = $this->getFieldRules($field, $mode, $model);

            if (!empty($fieldRules)) {
                $rules[$field->key()] = $fieldRules;
            }
        }

        // Adjust unique rules for edit mode
        if ($mode === 'edit' && $model) {
            $rules = $this->adjustUniqueRulesForEdit($rules, $model);
        }

        return $rules;
    }

    /**
     * Get rules for a single field
     *
     * @param mixed $field Field instance
     * @param string $mode Create or edit mode
     * @param Model|null $model Model instance
     * @return array|string Rules array or pipe-separated string
     */
    protected function getFieldRules($field, string $mode, ?Model $model): array|string
    {
        $rules = [];

        // Handle Fieldset recursively
        if ($field instanceof Fieldset) {
            return $this->getFieldsetRules($field, $mode, $model);
        }

        $fieldRules = $field->getRules();

        // If field.getRules() returns string, convert to array
        if (is_string($fieldRules)) {
            $rules = array_filter(explode('|', $fieldRules));
        } else {
            $rules = array_filter((array) $fieldRules);
        }

        // Add required rule if field is required
        if ($field->isRequired()) {
            if (!in_array('required', $rules)) {
                array_unshift($rules, 'required');
            }
        } else {
            // Add nullable if not required
            if (!in_array('nullable', $rules)) {
                array_unshift($rules, 'nullable');
            }
        }

        // Return as pipe-separated string
        return implode('|', $rules);
    }

    /**
     * Get rules for Fieldset fields (nested validation)
     *
     * @param Fieldset $fieldset Fieldset instance
     * @param string $mode Create or edit mode
     * @param Model|null $model Model instance
     * @return array Nested rules with dot notation
     */
    protected function getFieldsetRules($fieldset, string $mode, ?Model $model): array
    {
        $rules = [];
        $fieldsetKey = $fieldset->key();

        // Get fields from Fieldset schema if method exists
        $subFields = method_exists($fieldset, 'getFields') ? $fieldset->getFields() : [];

        foreach ($subFields as $subField) {
            $subRules = $this->getFieldRules($subField, $mode, $model);

            if (!empty($subRules)) {
                // Use dot notation for nested fields
                $rules[$fieldsetKey . '.*.' . $subField->key()] = $subRules;
            }
        }

        return $rules;
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
