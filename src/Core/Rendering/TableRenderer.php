<?php

namespace Monstrex\Ave\Core\Rendering;

use Monstrex\Ave\Core\Table;

class TableRenderer
{
    /**
     * Render a table
     *
     * @param Table $table
     * @param $records
     * @param array $options
     * @return array
     */
    public function render(Table $table, $records, array $options = []): array
    {
        return [
            'columns' => $this->prepareColumns($table),
            'records' => $records,
            'sortable' => $table->isSortable(),
            'searchable' => $table->isSearchable(),
            'perPage' => $table->getPerPage(),
            'defaultSort' => $table->getDefaultSort(),
        ];
    }

    /**
     * Prepare column data for rendering
     *
     * @param Table $table
     * @return array
     */
    protected function prepareColumns(Table $table): array
    {
        $columns = [];

        foreach ($table->getColumns() as $column) {
            $columns[] = [
                'name' => $column->getName(),
                'label' => $column->getLabel(),
                'sortable' => $column->isSortable(),
                'searchable' => $column->isSearchable(),
                'format' => $column->getFormat(),
                'width' => $column->getWidth() ?? 'auto',
                'align' => $column->getAlign() ?? 'left',
            ];
        }

        return $columns;
    }
}
