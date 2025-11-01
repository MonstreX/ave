<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ModelDataSource;
use Monstrex\Ave\Core\Forms\FormContext;

abstract class AbstractField implements FormField
{
    public const TYPE = 'abstract';

    protected string $key;
    protected ?string $label = null;
    protected ?string $help = null;
    protected mixed $default = null;
    protected mixed $value = null;
    protected array $rules = [];
    protected bool $required = false;
    protected bool $disabled = false;
    protected ?string $placeholder = null;
    protected ?string $view = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;
        return $this;
    }

    public function type(): string
    {
        return static::TYPE;
    }

    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function help(?string $help): static
    {
        $this->help = $help;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        if ($required && !in_array('required', $this->rules)) {
            $this->rules[] = 'required';
        }
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->key;
    }

    public function getHelpText(): ?string
    {
        return $this->help;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function toArray(): array
    {
        return [
            'key'         => $this->key,
            'type'        => $this->type(),
            'label'       => $this->label,
            'help'        => $this->help,
            'default'     => $this->default,
            'rules'       => $this->rules,
            'required'    => $this->required,
            'disabled'    => $this->disabled,
            'placeholder' => $this->placeholder,
        ];
    }

    public function extract(mixed $raw): mixed
    {
        return $raw;
    }

    /**
     * Fill field value from a data source
     */
    public function fillFromDataSource(DataSourceInterface $source): void
    {
        $value = $source->get($this->key);
        $this->value = $value;
    }

    /**
     * Fill field value from Eloquent model
     */
    public function fillFromModel(mixed $model): void
    {
        $dataSource = new ModelDataSource($model);
        $this->fillFromDataSource($dataSource);
    }

    /**
     * Apply field value to a data source
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        $source->set($this->key, $value);
    }

    /**
     * Get the field's current value
     */
    public function getValue(): mixed
    {
        return $this->value ?? $this->default;
    }

    /**
     * Set the field's value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Render field to HTML
     *
     * This is the primary rendering method that:
     * 1. Ensures field is prepared for display
     * 2. Determines the Blade template to use
     * 3. Extracts error and context information for templates
     * 4. Passes all necessary data to template
     *
     * @param FormContext $context Form context for field preparation and data access
     * @return string Rendered HTML
     */
    public function render(FormContext $context): string
    {
        // Ensure field is prepared for display
        if (method_exists($this, 'prepareForDisplay')) {
            // Only prepare if value is not yet set
            if (is_null($this->value) && is_null($this->getValue())) {
                $this->prepareForDisplay($context);
            }
        }

        // Determine view template
        // Priority: custom view > default naming convention
        $view = $this->view ?? 'ave::components.forms.' . str_replace('_', '-', $this->type());

        // Convert field to array for template compatibility
        $fieldData = $this->toArray();
        $fieldData['value'] = $this->getValue();

        // Extract error information from context
        $hasError = $context->hasError($this->key);
        $errors = $context->getErrors($this->key);

        // Render template with all necessary data
        return view($view, [
            'field'      => $this,              // Field object for method calls
            'context'    => $context,           // FormContext for nested processing
            'hasError'   => $hasError,          // Error flag for CSS classes
            'errors'     => $errors,            // Error messages array
            'attributes' => '',                 // HTML attributes string (for compatibility)
            ...$fieldData,                      // Spread array for variable compatibility
        ])->render();
    }
}
