<?php

namespace Monstrex\Ave\Core;

use Monstrex\Ave\Contracts\FormField;

class FormColumn
{
    /**
     * @var array<FormField>
     */
    protected array $fields = [];

    protected int $span = 12; // 1-12 (Bootstrap-like grid)

    public static function make(): static
    {
        return new static();
    }

    /**
     * @param array<FormField> $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function addField(FormField $field): static
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Set column width (1-12).
     */
    public function span(int $span): static
    {
        $this->span = max(1, min(12, $span));

        return $this;
    }

    /**
     * @return array<FormField>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getSpan(): int
    {
        return $this->span;
    }

    public function toArray(): array
    {
        return [
            'span' => $this->span,
            'fields' => array_map(
                fn (FormField $field) => $field->toArray(),
                $this->fields
            ),
        ];
    }
}
