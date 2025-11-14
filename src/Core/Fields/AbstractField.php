<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Support\Str;
use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Contracts\NestableField;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ModelDataSource;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\Concerns\HasContainer;
use Monstrex\Ave\Core\Fields\Concerns\HasStatePath;
use Monstrex\Ave\Core\Fields\Concerns\IsTemplate;
use Monstrex\Ave\Support\FormInputName;

abstract class AbstractField implements FormField, NestableField
{
    use HasContainer;
    use HasStatePath;
    use IsTemplate;

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
    protected string $displayVariant = 'default';
    protected bool $sensitive = false;

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
        return Str::kebab(class_basename($this));
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

    public function sensitive(bool $sensitive = true): static
    {
        $this->sensitive = $sensitive;

        return $this;
    }

    public function isSensitive(): bool
    {
        return $this->sensitive;
    }

    public function template(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function displayAs(string $variant): static
    {
        $this->displayVariant = $variant;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
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

        $hasError = $context->hasError($this->key);
        $errors = $context->getErrors($this->key);
        $statePath = $this->getStatePath();
        $inputName = FormInputName::nameFromStatePath($statePath);
        $inputId = FormInputName::idFromStatePath($statePath);

        return view($view, [
            'field' => $this,
            'context' => $context,
            'hasError' => $hasError,
            'errors' => $errors,
            'attributes' => '',
            'statePath' => $statePath,
            'inputName' => $inputName,
            'inputId' => $inputId,
            ...$fieldData,
        ])->render();
    }

    protected function resolveDefaultView(): string
    {
        $template = Str::kebab($this->type());
        $variant = $this->displayVariant;

        // If not default variant, try to find variant-specific view
        if ($variant !== 'default') {
            $variantView = "ave::components.forms.fields.{$template}.{$variant}";
            if (view()->exists($variantView)) {
                return $variantView;
            }
        }

        // Try default variant view (fieldset/default.blade.php pattern)
        $defaultView = "ave::components.forms.fields.{$template}.default";
        if (view()->exists($defaultView)) {
            return $defaultView;
        }

        // Fallback to base view
        return "ave::components.forms.fields.{$template}";
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
