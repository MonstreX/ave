<?php

namespace Monstrex\Ave\Core;

class FormColumn
{
    /** @var array */
    protected array $fields = [];

    protected int $span = 12; // 1-12 (Bootstrap-like grid)

    public static function make(): static
    {
        return new static();
    }

    /**
     * Define fields for this column
     *
     * @param array $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Add single field
     */
    public function addField($field): static
    {
        $this->fields[] = $field;
        return $this;
    }

    /**
     * Set column width (1-12)
     */
    public function span(int $span): static
    {
        $this->span = max(1, min(12, $span));
        return $this;
    }

    /**
     * Get all fields
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function toArray(): array
    {
        return [
            'span'   => $this->span,
            'fields' => array_map(fn($f) => is_object($f) && method_exists($f, 'toArray') ? $f->toArray() : $f, $this->fields),
        ];
    }
}
