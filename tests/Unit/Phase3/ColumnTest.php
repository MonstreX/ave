<?php

namespace Monstrex\Ave\Tests\Unit\Phase3;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Columns\Column;

class ColumnTest extends TestCase
{
    protected Column $column;

    protected function setUp(): void
    {
        $this->column = Column::make('name');
    }

    public function test_column_can_be_created()
    {
        $this->assertInstanceOf(Column::class, $this->column);
    }

    public function test_column_fluent_interface()
    {
        $result = $this->column->label('Full Name')->sortable()->searchable();
        $this->assertInstanceOf(Column::class, $result);
    }

    public function test_column_key()
    {
        $this->assertEquals('name', $this->column->key());
    }

    public function test_column_is_searchable()
    {
        $this->assertFalse($this->column->isSearchable());
        $this->column->searchable();
        $this->assertTrue($this->column->isSearchable());
    }

    public function test_column_is_sortable()
    {
        $this->assertFalse($this->column->isSortable());
        $this->column->sortable();
        $this->assertTrue($this->column->isSortable());
    }

    public function test_column_format()
    {
        $this->column->format(fn($value) => strtoupper($value));
        $formatted = $this->column->formatValue('john', null);
        $this->assertEquals('JOHN', $formatted);
    }

    public function test_column_to_array()
    {
        $this->column->label('Name')->sortable()->width(150);
        $array = $this->column->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('name', $array['key']);
        $this->assertEquals('Name', $array['label']);
        $this->assertTrue($array['sortable']);
        $this->assertEquals(150, $array['width']);
    }
}
