<?php

namespace Monstrex\Ave\Core\Fields;

use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ModelDataSource;

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

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Get validation rules for the field
     */
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
     *
     * Works with any DataSourceInterface implementation:
     * - ModelDataSource: for Eloquent models
     * - ArrayDataSource: for arrays/JSON (used in FieldSet)
     *
     * @param  DataSourceInterface  $source
     * @return void
     */
    public function fillFromDataSource(DataSourceInterface $source): void
    {
        $value = $source->get($this->key);
        $this->value = $value;
    }

    /**
     * Fill field value from Eloquent model
     *
     * Backward compatibility method that delegates to fillFromDataSource()
     *
     * @param  mixed  $model  Eloquent model instance
     * @return void
     */
    public function fillFromModel(mixed $model): void
    {
        $dataSource = new ModelDataSource($model);
        $this->fillFromDataSource($dataSource);
    }

    /**
     * Apply field value to a data source
     *
     * Works with any DataSourceInterface implementation
     *
     * @param  DataSourceInterface  $source
     * @param  mixed  $value
     * @return void
     */
    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        $source->set($this->key, $value);
    }

    /**
     * Get the field's current value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value ?? $this->default;
    }

    /**
     * Set the field's value
     *
     * @param  mixed  $value
     * @return void
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
