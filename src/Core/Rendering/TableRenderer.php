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
            'sortable' => true,
            'searchable' => true,
            'perPage' => 25,
            'defaultSort' => null,
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
            $data = $column->toArray();

            $columns[] = [
                'name' => $data['key'] ?? 'unknown',
                'label' => $data['label'] ?? $data['key'] ?? 'unknown',
                'sortable' => $data['sortable'] ?? false,
                'searchable' => $data['searchable'] ?? false,
                'format' => $data['formatCallback'] ?? null,
                'width' => $data['width'] ?? 'auto',
                'align' => $data['align'] ?? 'left',
            ];
        }

        return $columns;
    }
}
