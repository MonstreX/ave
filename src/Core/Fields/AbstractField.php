<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Support\Str;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Contracts\NestableField;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ModelDataSource;
use Monstrex\Ave\Core\FormContext;

abstract class AbstractField implements FormField, NestableField
{
    public const TYPE = 'abstract';

    protected string $key;
    protected string $baseKey;
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
        $this->baseKey = $key;
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

        if ($required && !in_array('required', $this->rules, true)) {
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

    public function template(string $view): static
    {
        $this->view = $view;

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
            'key' => $this->key,
            'type' => $this->type(),
            'label' => $this->label,
            'help' => $this->help,
            'default' => $this->default,
            'rules' => $this->rules,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'placeholder' => $this->placeholder,
        ];
    }

    public function extract(mixed $raw): mixed
    {
        return $raw;
    }

    public function fillFromDataSource(DataSourceInterface $source): void
    {
        $this->value = $source->get($this->key);
    }

    public function fillFromModel(mixed $model): void
    {
        $this->fillFromDataSource(new ModelDataSource($model));
    }

    public function applyToDataSource(DataSourceInterface $source, mixed $value): void
    {
        $source->set($this->key, $value);
    }

    public function getValue(): mixed
    {
        return $this->value ?? $this->default;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function render(FormContext $context): string
    {
        if ($context->hasOldInput($this->key)) {
            $this->value = $context->oldInput($this->key);
        }

        if (method_exists($this, 'prepareForDisplay') && $this->getValue() === null) {
            $this->prepareForDisplay($context);
        }

        $view = $this->view ?? $this->resolveDefaultView();

        $fieldData = $this->toArray();
        $fieldData['value'] = $this->getValue();

        return view($view, [
            'field' => $this,
            'context' => $context,
            'hasError' => $context->hasError($this->key),
            'errors' => $context->getErrors($this->key),
            'attributes' => '',
            ...$fieldData,
        ])->render();
    }

    protected function resolveDefaultView(): string
    {
        $map = [
            'text' => 'text-input',
            'number' => 'number-input',
            'datetime' => 'datetime-input',
            'richtext' => 'rich-editor',
            'file' => 'media-field',
        ];

        $type = Str::kebab($this->type());
        $template = $map[$type] ?? $type;

        return "ave::components.forms.{$template}";
    }

    public function baseKey(): string
    {
        return $this->baseKey;
    }

    public function nestWithin(string $parentKey, string $itemIdentifier): static
    {
        $clone = clone $this;

        $clone->key = sprintf('%s[%s][%s]', $parentKey, $itemIdentifier, $this->baseKey());

        return $clone;
    }
}
