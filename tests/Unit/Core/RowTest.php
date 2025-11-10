<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Contracts\FormField;

/**
 * RowTest - Unit tests for Row class.
 *
 * Tests the row component which provides:
 * - Grid-based layout with columns
 * - Column management (add, set, retrieve)
 * - Fluent interface for configuration
 * - Array serialization for rendering
 */
class RowTest extends TestCase
{
    /**
     * Test row can be instantiated
     */
    public function test_row_can_be_instantiated(): void
    {
        $row = new Row();
        $this->assertInstanceOf(Row::class, $row);
    }

    /**
     * Test row make factory method
     */
    public function test_row_make_factory_method(): void
    {
        $row = Row::make();
        $this->assertInstanceOf(Row::class, $row);
    }

    /**
     * Test row columns method is fluent
     */
    public function test_row_columns_method_is_fluent(): void
    {
        $row = new Row();
        $result = $row->columns([]);

        $this->assertInstanceOf(Row::class, $result);
        $this->assertSame($row, $result);
    }

    /**
     * Test row add column method is fluent
     */
    public function test_row_add_column_method_is_fluent(): void
    {
        $row = new Row();
        $col = Col::make();

        $result = $row->addColumn($col);

        $this->assertInstanceOf(Row::class, $result);
        $this->assertSame($row, $result);
    }

    /**
     * Test row get columns returns empty array
     */
    public function test_row_get_columns_empty(): void
    {
        $row = new Row();
        $columns = $row->getColumns();

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /**
     * Test row get columns returns set columns
     */
    public function test_row_get_columns_returns_columns(): void
    {
        $row = new Row();
        $col1 = Col::make(6);
        $col2 = Col::make(6);

        $row->columns([$col1, $col2]);
        $columns = $row->getColumns();

        $this->assertCount(2, $columns);
        $this->assertSame($col1, $columns[0]);
        $this->assertSame($col2, $columns[1]);
    }

    /**
     * Test row add column single
     */
    public function test_row_add_column_single(): void
    {
        $row = new Row();
        $col = Col::make(12);

        $row->addColumn($col);
        $columns = $row->getColumns();

        $this->assertCount(1, $columns);
        $this->assertSame($col, $columns[0]);
    }

    /**
     * Test row add column multiple
     */
    public function test_row_add_column_multiple(): void
    {
        $row = new Row();
        $col1 = Col::make(6);
        $col2 = Col::make(6);

        $row->addColumn($col1)->addColumn($col2);
        $columns = $row->getColumns();

        $this->assertCount(2, $columns);
    }

    /**
     * Test row columns sets columns
     */
    public function test_row_columns_sets_columns(): void
    {
        $row = new Row();
        $col = Col::make(12);

        $row->columns([$col]);

        $this->assertCount(1, $row->getColumns());
    }

    /**
     * Test row columns replaces previous columns
     */
    public function test_row_columns_replaces_previous(): void
    {
        $row = new Row();
        $col1 = Col::make(6);
        $col2 = Col::make(6);
        $col3 = Col::make(12);

        $row->columns([$col1, $col2]);
        $this->assertCount(2, $row->getColumns());

        $row->columns([$col3]);
        $this->assertCount(1, $row->getColumns());
        $this->assertSame($col3, $row->getColumns()[0]);
    }

    /**
     * Test row to array structure
     */
    public function test_row_to_array_structure(): void
    {
        $row = new Row();
        $col = Col::make(12);

        $row->addColumn($col);
        $array = $row->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('columns', $array);
        $this->assertIsArray($array['columns']);
    }

    /**
     * Test row to array empty columns
     */
    public function test_row_to_array_empty(): void
    {
        $row = new Row();
        $array = $row->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('columns', $array);
        $this->assertEmpty($array['columns']);
    }

    /**
     * Test row to array with columns
     */
    public function test_row_to_array_with_columns(): void
    {
        $row = new Row();
        $col1 = Col::make(6);
        $col2 = Col::make(6);

        $row->columns([$col1, $col2]);
        $array = $row->toArray();

        $this->assertCount(2, $array['columns']);
    }

    /**
     * Test row fluent interface chaining
     */
    public function test_row_fluent_interface_chaining(): void
    {
        $col1 = Col::make(6);
        $col2 = Col::make(6);

        $result = Row::make()
            ->addColumn($col1)
            ->addColumn($col2);

        $this->assertInstanceOf(Row::class, $result);
        $this->assertCount(2, $result->getColumns());
    }

    /**
     * Test row with different column spans
     */
    public function test_row_with_different_column_spans(): void
    {
        $row = Row::make();
        $col1 = Col::make(4);
        $col2 = Col::make(4);
        $col3 = Col::make(4);

        $row->columns([$col1, $col2, $col3]);

        $this->assertCount(3, $row->getColumns());
        $this->assertEquals(4, $row->getColumns()[0]->getSpan());
        $this->assertEquals(4, $row->getColumns()[1]->getSpan());
        $this->assertEquals(4, $row->getColumns()[2]->getSpan());
    }

    /**
     * Test row columns method with empty array
     */
    public function test_row_columns_empty_array(): void
    {
        $row = Row::make();
        $col = Col::make(12);

        $row->addColumn($col);
        $this->assertCount(1, $row->getColumns());

        $row->columns([]);
        $this->assertEmpty($row->getColumns());
    }

    /**
     * Test multiple row instances independence
     */
    public function test_multiple_row_instances(): void
    {
        $row1 = Row::make();
        $row2 = Row::make();

        $col = Col::make(12);

        $row1->addColumn($col);

        $this->assertCount(1, $row1->getColumns());
        $this->assertEmpty($row2->getColumns());
    }

    /**
     * Test row with columns containing fields
     */
    public function test_row_with_columns_containing_fields(): void
    {
        $row = Row::make();
        $col = Col::make(12);
        $field = $this->createMock(FormField::class);

        $col->addField($field);
        $row->addColumn($col);

        $columns = $row->getColumns();
        $this->assertCount(1, $columns);
    }

    /**
     * Test row to array maps columns correctly
     */
    public function test_row_to_array_maps_columns(): void
    {
        $row = Row::make();
        $col1 = Col::make(6);
        $col2 = Col::make(6);

        $row->columns([$col1, $col2]);
        $array = $row->toArray();

        $this->assertIsArray($array['columns']);
        $this->assertCount(2, $array['columns']);
    }

    /**
     * Test row method visibility
     */
    public function test_row_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(Row::class);

        $publicMethods = [
            'make',
            'columns',
            'addColumn',
            'getColumns',
            'toArray'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Row should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test row namespace
     */
    public function test_row_namespace(): void
    {
        $reflection = new \ReflectionClass(Row::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test row class name
     */
    public function test_row_class_name(): void
    {
        $reflection = new \ReflectionClass(Row::class);
        $this->assertEquals('Row', $reflection->getShortName());
    }

    /**
     * Test row constructor is public
     */
    public function test_row_constructor_is_public(): void
    {
        $reflection = new \ReflectionClass(Row::class);
        $constructor = $reflection->getConstructor();

        // If constructor is not explicitly defined, it's public by default
        if ($constructor) {
            $this->assertTrue($constructor->isPublic());
        } else {
            // No explicit constructor means default public constructor
            $this->assertTrue(true);
        }
    }

    /**
     * Test row columns property initialization
     */
    public function test_row_columns_property_initialization(): void
    {
        $row = new Row();
        $columns = $row->getColumns();

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /**
     * Test row add many columns
     */
    public function test_row_add_many_columns(): void
    {
        $row = Row::make();

        for ($i = 0; $i < 12; $i++) {
            $col = Col::make(1);
            $row->addColumn($col);
        }

        $this->assertCount(12, $row->getColumns());
    }

    /**
     * Test row to array returns consistent structure
     */
    public function test_row_to_array_consistent_structure(): void
    {
        $row = Row::make();
        $col = Col::make(12);

        $row->addColumn($col);

        $array1 = $row->toArray();
        $array2 = $row->toArray();

        $this->assertEquals($array1, $array2);
    }

    /**
     * Test row columns method with single column
     */
    public function test_row_columns_with_single_column(): void
    {
        $row = Row::make();
        $col = Col::make(12);

        $row->columns([$col]);

        $this->assertCount(1, $row->getColumns());
        $this->assertSame($col, $row->getColumns()[0]);
    }
}
