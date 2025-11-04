<?php

namespace Monstrex\Ave\Core\Components;

use Monstrex\Ave\Contracts\FormField;
use Monstrex\Ave\Core\FormColumn;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\FormRow;

/**
 * Lightweight adapter turning FormRow into a reusable component.
 */
class RowComponent extends FormComponent
{
    public function __construct(
        protected FormRow $row,
    ) {
    }

    public static function fromFormRow(FormRow $row): self
    {
        return new self($row);
    }

    public function getFormRow(): FormRow
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
                static fn (FormColumn $column): array => [
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
                static fn (FormColumn $column): array => [
                    'span' => $column->getSpan(),
                    'fields' => $column->getFields(),
                ],
                $this->row->getColumns()
            ),
            'component' => $this,
        ];
    }
}

