<?php

namespace Monstrex\Ave\Core;

/**
 * Row - container for columns with grid-based layout
 */
class Row
{
    /**
     * @var array<Col>
     */
    protected array $columns = [];

    public static function make(): static
    {
        return new static();
    }

    /**
     * @param array<Col> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function addColumn(Col $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * @return array<Col>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function toArray(): array
    {
        return [
            'columns' => array_map(static fn (Col $column) => $column->toArray(), $this->columns),
        ];
    }
}
