<?php

namespace Monstrex\Ave\Core;

class Form
{
    /** @var array */
    protected array $rows = [];

    protected ?string $submitLabel = null;
    protected ?string $cancelUrl = null;

    public static function make(): static
    {
        return new static();
    }

    /**
     * Define form schema using rows
     *
     * @param array $rows
     */
    public function schema(array $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Add single row to form
     */
    public function addRow(FormRow $row): static
    {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Quick helper: add fields in single column
     *
     * @param array $fields
     */
    public function fields(array $fields): static
    {
        $row = FormRow::make()->columns([
            FormColumn::make()->fields($fields)->span(12)
        ]);

        $this->rows[] = $row;
        return $this;
    }

    public function submitLabel(string $label): static
    {
        $this->submitLabel = $label;
        return $this;
    }

    public function cancelUrl(string $url): static
    {
        $this->cancelUrl = $url;
        return $this;
    }

    /**
     * Get all form rows
     *
     * @return array
     */
    public function rows(): array
    {
        return array_map(fn($r) => $r->toArray(), $this->rows);
    }

    /**
     * Get all fields (flattened from all rows/columns)
     *
     * @return array
     */
    public function getAllFields(): array
    {
        $fields = [];

        foreach ($this->rows as $row) {
            foreach ($row->getColumns() as $column) {
                foreach ($column->getFields() as $field) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * Find field by key
     */
    public function getField(string $key)
    {
        foreach ($this->getAllFields() as $field) {
            if ($field->key() === $key) {
                return $field;
            }
        }

        return null;
    }
}
