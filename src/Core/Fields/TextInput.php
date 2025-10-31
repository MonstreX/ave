<?php

namespace Monstrex\Ave\Core\Fields;

class TextInput extends AbstractField
{
    public const TYPE = 'text';

    protected ?string $maxLength = null;
    protected ?string $minLength = null;
    protected ?string $pattern = null;

    public function maxLength(int $length): static
    {
        $this->maxLength = $length;
        return $this;
    }

    public function minLength(int $length): static
    {
        $this->minLength = $length;
        return $this;
    }

    public function pattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'maxLength' => $this->maxLength,
            'minLength' => $this->minLength,
            'pattern'   => $this->pattern,
        ]);
    }
}
