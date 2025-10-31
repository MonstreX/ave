<?php

namespace Monstrex\Ave\Core;

class FormRow
{
    /** @var array */
    protected array $columns = [];

    public static function make(): static
    {
        return new static();
    }

    /**
     * Define columns for this row
     *
     * @param array $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add single column
     */
    public function addColumn(FormColumn $column): static
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Get all columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function toArray(): array
    {
        return [
            'columns' => array_map(fn($c) => $c->toArray(), $this->columns),
        ];
    }
}
