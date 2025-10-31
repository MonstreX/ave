<?php

namespace Monstrex\Ave\Tests\Unit\Phase7;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Rendering\TableRenderer;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Columns\Column;
use Illuminate\Support\Collection;

class TableRendererTest extends TestCase
{
    protected TableRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new TableRenderer();
    }

    public function test_table_renderer_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TableRenderer::class, $this->renderer);
    }

    public function test_render_returns_array(): void
    {
        $table = Table::make()
            ->addColumn(Column::make('name')->label('Name'))
            ->addColumn(Column::make('email')->label('Email'));

        $records = collect([]);

        $result = $this->renderer->render($table, $records);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('columns', $result);
        $this->assertArrayHasKey('records', $result);
        $this->assertArrayHasKey('sortable', $result);
        $this->assertArrayHasKey('searchable', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('defaultSort', $result);
    }

    public function test_render_columns_data(): void
    {
        $table = Table::make()
            ->addColumn(Column::make('name')->label('Name')->sortable())
            ->addColumn(Column::make('email')->label('Email')->sortable());

        $records = collect([]);

        $result = $this->renderer->render($table, $records);

        $this->assertCount(2, $result['columns']);
        $this->assertEquals('name', $result['columns'][0]['name']);
        $this->assertEquals('Name', $result['columns'][0]['label']);
        $this->assertTrue($result['columns'][0]['sortable']);
    }

    public function test_prepare_columns(): void
    {
        $table = Table::make()
            ->addColumn(Column::make('id')->label('ID'))
            ->addColumn(Column::make('status')->label('Status')->sortable(false));

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('prepareColumns');
        $method->setAccessible(true);

        $columns = $method->invoke($this->renderer, $table);

        $this->assertCount(2, $columns);
        $this->assertFalse($columns[1]['sortable']);
    }
}
