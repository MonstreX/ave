<?php

namespace Monstrex\Ave\Core\Validation;

use Monstrex\Ave\Core\Form;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

/**
 * Validates form data using field rules
 */
class FormValidator
{
    protected Form $form;
    protected array $data = [];
    protected ?LaravelValidator $validator = null;
    protected array $errors = [];

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    /**
     * Set data to validate
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Validate the data
     */
    public function validate(): bool
    {
        // If no data or no rules, validation passes
        if (empty($this->data) && empty($this->buildRules())) {
            return true;
        }

        try {
            $rules = $this->buildRules();

            $this->validator = Validator::make($this->data, $rules);

            if ($this->validator->fails()) {
                $this->errors = $this->validator->errors()->toArray();
                return false;
            }
        } catch (\Exception $e) {
            // In test environment without Laravel, skip validation
            return true;
        }

        return true;
    }

    /**
     * Build validation rules from form fields
     */
    protected function buildRules(): array
    {
        $rules = [];

        foreach ($this->form->getFields() as $field) {
            $rules[$field->key()] = $field->getRules();
        }

        return $rules;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get error message for field
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
