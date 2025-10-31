<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Table;

class TableTest extends TestCase
{
    protected Table $table;

    protected function setUp(): void
    {
        $this->table = Table::make();
    }

    public function test_table_can_be_instantiated()
    {
        $this->assertInstanceOf(Table::class, $this->table);
    }

    public function test_table_make_returns_new_instance()
    {
        $table1 = Table::make();
        $table2 = Table::make();
        $this->assertNotSame($table1, $table2);
    }

    public function test_table_fluent_interface()
    {
        $result = $this->table->perPage(50)->searchable(true);
        $this->assertInstanceOf(Table::class, $result);
    }

    public function test_table_get_configuration()
    {
        $config = $this->table
            ->perPage(50)
            ->searchable(true)
            ->get();

        $this->assertIsArray($config);
        $this->assertEquals(50, $config['perPage']);
        $this->assertTrue($config['searchable']);
    }

    public function test_table_default_sort()
    {
        $this->table->defaultSort('name', 'asc');
        $config = $this->table->get();

        $this->assertNotNull($config['defaultSort']);
        $this->assertEquals('name', $config['defaultSort'][0]);
        $this->assertEquals('asc', $config['defaultSort'][1]);
    }

    public function test_table_add_column()
    {
        $this->table->addColumn('name');
        $columns = $this->table->getColumns();

        $this->assertCount(1, $columns);
        $this->assertEquals('name', $columns[0]);
    }

    public function test_table_columns_method()
    {
        $this->table->columns(['name', 'email', 'created_at']);
        $columns = $this->table->getColumns();

        $this->assertCount(3, $columns);
    }
}
