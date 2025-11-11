<?php

namespace Monstrex\Ave\Core\Criteria;

class ActionBadge
{
    public function __construct(
        protected string $label,
        protected ?string $key = null,
        protected mixed $value = null,
        protected ?string $variant = null,
    ) {
    }

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function key(?string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function value(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function variant(?string $variant): self
    {
        $this->variant = $variant;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'key' => $this->key,
            'value' => $this->value,
            'variant' => $this->variant,
        ];
    }
}

