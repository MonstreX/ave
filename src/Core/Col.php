<?php

namespace Monstrex\Ave\Core;

use Monstrex\Ave\Contracts\FormField;

/**
 * Col - column container within Row with span control (1-12, Bootstrap-like)
 */
class Col
{
    /**
     * @var array<FormField>
     */
    protected array $fields = [];

    protected int $span = 12; // 1-12 (Bootstrap-like grid)

    public static function make(int $span = 12): static
    {
        $instance = new static();
        $instance->span = max(1, min(12, $span));

        return $instance;
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
