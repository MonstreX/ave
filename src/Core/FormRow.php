<?php

namespace Monstrex\Ave\Core;

class FormRow
{
    /**
     * @var array<FormColumn>
     */
    protected array $columns = [];

    public static function make(): static
    {
        return new static();
    }

    /**
     * @param array<FormColumn> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function addColumn(FormColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * @return array<FormColumn>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function toArray(): array
    {
        return [
            'columns' => array_map(static fn (FormColumn $column) => $column->toArray(), $this->columns),
        ];
    }
}
