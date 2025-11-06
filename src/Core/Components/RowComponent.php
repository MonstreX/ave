<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Row;

/**
 * Lightweight adapter turning Row into a reusable component.
 */
class RowComponent extends FormComponent
{
    public function __construct(
        protected Row $row,
    ) {
    }

    public static function fromRow(Row $row): self
    {
        return new self($row);
    }

    public function getRow(): Row
    {
        return $this->row;
    }

    public function flattenFields(): array
    {
        $fields = [];

        foreach ($this->row->getColumns() as $column) {
            foreach ($column->getFields() as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function render(FormContext $context): string
    {
        return view('ave::components.forms.row', [
            'component' => $this,
            'columns' => array_map(
                static fn (Col $column): array => [
                    'span' => $column->getSpan(),
                    'fields' => $column->getFields(),
                ],
                $this->row->getColumns()
            ),
            'context' => $context,
        ])->render();
    }

    /**
     * Convert row to layout array used by legacy rendering pipeline.
     *
     * @return array<string,mixed>
     */
    public function toLayoutArray(): array
    {
        return [
            'type' => 'row',
            'columns' => array_map(
                static fn (Col $column): array => [
                    'span' => $column->getSpan(),
                    'fields' => $column->getFields(),
                ],
                $this->row->getColumns()
            ),
            'component' => $this,
        ];
    }
}

