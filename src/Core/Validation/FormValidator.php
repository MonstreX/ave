<?php

namespace Monstrex\Ave\Core\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monstrex\Ave\Contracts\HandlesFormRequest;
use Monstrex\Ave\Contracts\ProvidesValidationRules;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;

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
        ?Model $model = null,
        ?FormContext $context = null
    ): array {
        $context ??= $model
            ? FormContext::forEdit($model, [], $request)
            : FormContext::forCreate([], $request);

        foreach ($form->getAllFields() as $field) {
            if ($field instanceof HandlesFormRequest) {
                $field->prepareRequest($request, $context);
            }
        }

        $rules = [];

        foreach ($form->getAllFields() as $field) {
            if ($field instanceof ProvidesValidationRules) {
                $rules = array_merge($rules, $field->buildValidationRules());
                continue;
            }

            $rules = $this->appendFieldRules($rules, $field, $field->key());
        }

        if ($mode === 'edit' && $model) {
            $rules = $this->adjustUniqueRulesForEdit($rules, $model);
        }

        Log::debug('FormValidator generated rules', [
            'resource' => $resourceClass,
            'mode' => $mode,
            'rules' => $rules,
        ]);

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
            return $rules;
        }

        $rules[$key] = $this->formatRules($fieldRules, $field->isRequired());

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
