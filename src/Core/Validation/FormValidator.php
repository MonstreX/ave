<?php

namespace Monstrex\Ave\Core\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Form;

/**
 * Builds Laravel validation rules from form definition.
 */
class FormValidator
{
    /**
     * @return array<string,string>
     */
    public function rulesFromForm(
        Form $form,
        string $resourceClass,
        Request $request,
        string $mode = 'create',
        ?Model $model = null
    ): array {
        $rules = [];

        logger()->info('[FormValidator] building rules', [
            'resource' => $resourceClass,
            'mode' => $mode,
            'payload_keys' => array_keys($request->all()),
            'fieldset_input' => $request->input(),
        ]);

        foreach ($form->getAllFields() as $field) {
            if ($field instanceof Fieldset) {
                $rules += $this->fieldsetRules($field);
                continue;
            }

            $rules = $this->appendFieldRules($rules, $field, $field->key());
        }

        if ($mode === 'edit' && $model) {
            $rules = $this->adjustUniqueRulesForEdit($rules, $model);
        }

        logger()->info('[FormValidator] rules compiled', [
            'rules' => $rules,
        ]);

        return $rules;
    }

    protected function fieldsetRules(Fieldset $fieldset): array
    {
        $rules = [];

        $fieldsetKey = $fieldset->key();
        $rules = $this->appendFieldRules($rules, $fieldset, $fieldsetKey, $this->fieldsetBaseRules($fieldset));

        foreach ($fieldset->getChildSchema() as $nestedField) {
            if (!$nestedField instanceof AbstractField) {
                continue;
            }

            $nestedKey = sprintf('%s.*.%s', $fieldsetKey, $nestedField->key());
            $rules = $this->appendFieldRules($rules, $nestedField, $nestedKey);
        }

        return $rules;
    }

    /**
     * @param array<string,string> $rules
     * @param AbstractField $field
     * @param string $key
     * @param array<int,string>|null $baseRules
     * @return array<string,string>
     */
    protected function appendFieldRules(array $rules, AbstractField $field, string $key, ?array $baseRules = null): array
    {
        $fieldRules = $baseRules ?? $field->getRules();

        if (empty($fieldRules) && !$field->isRequired()) {
            logger()->info('[FormValidator] skip empty rules', [
                'field' => $field->key(),
                'key' => $key,
            ]);
            return $rules;
        }

        $rules[$key] = $this->formatRules($fieldRules, $field->isRequired());

        logger()->info('[FormValidator] append rules', [
            'field' => $field->key(),
            'key' => $key,
            'rules' => $rules[$key],
        ]);

        return $rules;
    }

    /**
     * Ensure base rules for Fieldset include array/min/max constraints.
     *
     * @return array<int,string>
     */
    protected function fieldsetBaseRules(Fieldset $fieldset): array
    {
        $rules = $fieldset->getRules();

        if (!in_array('array', $rules, true)) {
            $rules[] = 'array';
        }

        if (($min = $fieldset->getMinItems()) !== null) {
            $rules[] = 'min:' . $min;
        }

        if (($max = $fieldset->getMaxItems()) !== null) {
            $rules[] = 'max:' . $max;
        }

        return $rules;
    }

    /**
     * @param array<int,string>|string $rules
     */
    protected function formatRules(array|string $rules, bool $required = false): string
    {
        $rules = is_string($rules)
            ? array_filter(explode('|', $rules))
            : array_filter($rules);

        if ($required) {
            if (!in_array('required', $rules, true)) {
                array_unshift($rules, 'required');
            }
        } elseif (!in_array('nullable', $rules, true)) {
            array_unshift($rules, 'nullable');
        }

        return implode('|', $rules);
    }

    /**
     * @param array<string,string> $rules
     *
     * @return array<string,string>
     */
    protected function adjustUniqueRulesForEdit(array $rules, Model $model): array
    {
        foreach ($rules as $field => $fieldRules) {
            $segments = explode('|', $fieldRules);

            foreach ($segments as &$segment) {
                if (str_starts_with($segment, 'unique:')) {
                    $segment .= ',' . $model->getKey() . ',' . $model->getKeyName();
                }
            }

            $rules[$field] = implode('|', $segments);
        }

        return $rules;
    }
}
