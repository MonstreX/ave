<?php

namespace Monstrex\Ave\Core\Columns;

class BooleanColumn extends Column
{
    protected string $type = 'boolean';

    protected mixed $trueValue = 1;
    protected mixed $falseValue = 0;
    protected string $trueLabel = 'Yes';
    protected string $falseLabel = 'No';
    protected ?string $trueIcon = null;
    protected ?string $falseIcon = null;
    protected string $trueColor = 'success';
    protected string $falseColor = 'danger';
    protected bool $toggle = false;

    public static function make(string $key): static
    {
        return new static($key);
    }

    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;
        return $this;
    }

    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;
        return $this;
    }

    public function trueIcon(?string $icon): static
    {
        $this->trueIcon = $icon;
        return $this;
    }

    public function falseIcon(?string $icon): static
    {
        $this->falseIcon = $icon;
        return $this;
    }

    public function trueColor(string $color): static
    {
        $this->trueColor = $color;
        return $this;
    }

    public function falseColor(string $color): static
    {
        $this->falseColor = $color;
        return $this;
    }

    public function trueValue(mixed $value): static
    {
        $this->trueValue = $value;
        return $this;
    }

    public function falseValue(mixed $value): static
    {
        $this->falseValue = $value;
        return $this;
    }

    public function showAsToggle(bool $on = true): static
    {
        $this->toggle = $on;
        return $this;
    }

    public function inlineToggle(?array $options = null): static
    {
        $this->showAsToggle(true);
        $field = $options['field'] ?? $this->key();
        $this->inline('toggle', ['field' => $field]);
        $allowed = collect([$this->trueValue, $this->falseValue])
            ->map(fn ($value) => is_bool($value) ? (int) $value : $value)
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->implode(',');
        $this->inlineRules('required|in:' . $allowed);

        return $this;
    }

    public function isActive(mixed $value): bool
    {
        return (string) $value === (string) $this->trueValue;
    }

    public function getTrueLabel(): string
    {
        return $this->trueLabel;
    }

    public function getFalseLabel(): string
    {
        return $this->falseLabel;
    }

    public function getTrueIcon(): ?string
    {
        return $this->trueIcon;
    }

    public function getFalseIcon(): ?string
    {
        return $this->falseIcon;
    }

    public function getTrueColor(): string
    {
        return $this->trueColor;
    }

    public function getFalseColor(): string
    {
        return $this->falseColor;
    }

    public function getTrueValue(): mixed
    {
        return $this->trueValue;
    }

    public function getFalseValue(): mixed
    {
        return $this->falseValue;
    }

    public function isToggleEnabled(): bool
    {
        return $this->toggle;
    }

    public function formatValue(mixed $value, mixed $record): mixed
    {
        return (string) $value;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'trueValue' => $this->trueValue,
            'falseValue' => $this->falseValue,
            'trueLabel' => $this->trueLabel,
            'falseLabel' => $this->falseLabel,
            'toggle' => $this->toggle,
        ]);
    }
}

