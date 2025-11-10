<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * TableTest - Unit tests for Table class.
 *
 * Tests the table system which provides:
 * - Column, filter, action, and bulk action management
 * - Pagination and search configuration
 * - Query filtering and search application
 * - Table configuration serialization
 * - Fluent interface for configuration
 */
class TableTest extends TestCase
{
    /**
     * Test table can be instantiated
     */
    public function test_table_can_be_instantiated(): void
    {
        $table = new Table();
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test table make factory method
     */
    public function test_table_make_factory_method(): void
    {
        $table = Table::make();
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test table columns method is fluent
     */
    public function test_table_columns_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->columns([]);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table add column method is fluent
     */
    public function test_table_add_column_method_is_fluent(): void
    {
        $table = new Table();
        $column = $this->createMock(\stdClass::class);

        $result = $table->addColumn($column);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table filters method is fluent
     */
    public function test_table_filters_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->filters([]);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table actions method is fluent
     */
    public function test_table_actions_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->actions([]);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table bulk actions method is fluent
     */
    public function test_table_bulk_actions_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->bulkActions([]);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table default sort method is fluent
     */
    public function test_table_default_sort_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->defaultSort('created_at', 'desc');

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table per page method is fluent
     */
    public function test_table_per_page_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->perPage(50);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table searchable method is fluent
     */
    public function test_table_searchable_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->searchable(true);

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table search placeholder method is fluent
     */
    public function test_table_search_placeholder_method_is_fluent(): void
    {
        $table = new Table();
        $result = $table->searchPlaceholder('Find items...');

        $this->assertInstanceOf(Table::class, $result);
        $this->assertSame($table, $result);
    }

    /**
     * Test table fluent interface chaining
     */
    public function test_table_fluent_interface_chaining(): void
    {
        $table = Table::make()
            ->columns([])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->perPage(50)
            ->searchable(true)
            ->searchPlaceholder('Search...')
            ->defaultSort('id', 'asc');

        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test table get columns returns columns
     */
    public function test_table_get_columns_returns_columns(): void
    {
        $table = new Table();
        $column = $this->createMock(\stdClass::class);

        $table->columns([$column]);
        $columns = $table->getColumns();

        $this->assertIsArray($columns);
        $this->assertCount(1, $columns);
    }

    /**
     * Test table get columns empty
     */
    public function test_table_get_columns_empty(): void
    {
        $table = new Table();
        $columns = $table->getColumns();

        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    /**
     * Test table get filters returns filters
     */
    public function test_table_get_filters_returns_filters(): void
    {
        $table = new Table();
        $filter = $this->createMock(\stdClass::class);

        $table->filters([$filter]);
        $filters = $table->getFilters();

        $this->assertIsArray($filters);
        $this->assertCount(1, $filters);
    }

    /**
     * Test table get filters empty
     */
    public function test_table_get_filters_empty(): void
    {
        $table = new Table();
        $filters = $table->getFilters();

        $this->assertIsArray($filters);
        $this->assertEmpty($filters);
    }

    /**
     * Test table get actions returns actions
     */
    public function test_table_get_actions_returns_actions(): void
    {
        $table = new Table();
        $action = $this->createMock(\Monstrex\Ave\Core\Actions\Action::class);

        $table->actions([$action]);
        $actions = $table->getActions();

        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);
    }

    /**
     * Test table get actions empty
     */
    public function test_table_get_actions_empty(): void
    {
        $table = new Table();
        $actions = $table->getActions();

        $this->assertIsArray($actions);
        $this->assertEmpty($actions);
    }

    /**
     * Test table get bulk actions returns actions
     */
    public function test_table_get_bulk_actions_returns_actions(): void
    {
        $table = new Table();
        $bulkAction = $this->createMock(\Monstrex\Ave\Core\Actions\BulkAction::class);

        $table->bulkActions([$bulkAction]);
        $bulkActions = $table->getBulkActions();

        $this->assertIsArray($bulkActions);
        $this->assertCount(1, $bulkActions);
    }

    /**
     * Test table get bulk actions empty
     */
    public function test_table_get_bulk_actions_empty(): void
    {
        $table = new Table();
        $bulkActions = $table->getBulkActions();

        $this->assertIsArray($bulkActions);
        $this->assertEmpty($bulkActions);
    }

    /**
     * Test table has bulk actions returns true
     */
    public function test_table_has_bulk_actions_true(): void
    {
        $table = new Table();
        $bulkAction = $this->createMock(\Monstrex\Ave\Core\Actions\BulkAction::class);

        $table->bulkActions([$bulkAction]);
        $result = $table->hasBulkActions();

        $this->assertTrue($result);
    }

    /**
     * Test table has bulk actions returns false
     */
    public function test_table_has_bulk_actions_false(): void
    {
        $table = new Table();
        $result = $table->hasBulkActions();

        $this->assertFalse($result);
    }

    /**
     * Test table is searchable returns true
     */
    public function test_table_is_searchable_true(): void
    {
        $table = Table::make()->searchable(true);
        $this->assertTrue($table->isSearchable());
    }

    /**
     * Test table is searchable returns false
     */
    public function test_table_is_searchable_false(): void
    {
        $table = Table::make()->searchable(false);
        $this->assertFalse($table->isSearchable());
    }

    /**
     * Test table default searchable is true
     */
    public function test_table_default_searchable(): void
    {
        $table = new Table();
        $this->assertTrue($table->isSearchable());
    }

    /**
     * Test table get per page default
     */
    public function test_table_get_per_page_default(): void
    {
        $table = new Table();
        $this->assertEquals(25, $table->getPerPage());
    }

    /**
     * Test table get per page custom
     */
    public function test_table_get_per_page_custom(): void
    {
        $table = Table::make()->perPage(50);
        $this->assertEquals(50, $table->getPerPage());
    }

    /**
     * Test table get search placeholder default
     */
    public function test_table_get_search_placeholder_default(): void
    {
        $table = new Table();
        $this->assertEquals('Search...', $table->getSearchPlaceholder());
    }

    /**
     * Test table get search placeholder custom
     */
    public function test_table_get_search_placeholder_custom(): void
    {
        $table = Table::make()->searchPlaceholder('Find items...');
        $this->assertEquals('Find items...', $table->getSearchPlaceholder());
    }

    /**
     * Test table get default sort
     */
    public function test_table_get_default_sort(): void
    {
        $table = Table::make()->defaultSort('created_at', 'desc');
        $sort = $table->getDefaultSort();

        $this->assertIsArray($sort);
        $this->assertEquals('created_at', $sort[0]);
        $this->assertEquals('desc', $sort[1]);
    }

    /**
     * Test table get default sort returns null
     */
    public function test_table_get_default_sort_null(): void
    {
        $table = new Table();
        $this->assertNull($table->getDefaultSort());
    }

    /**
     * Test table default sort direction default is desc
     */
    public function test_table_default_sort_direction_default(): void
    {
        $table = Table::make()->defaultSort('id');
        $sort = $table->getDefaultSort();

        $this->assertEquals('desc', $sort[1]);
    }

    /**
     * Test table get method returns configuration array
     */
    public function test_table_get_returns_configuration(): void
    {
        $table = Table::make();
        $config = $table->get();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('columns', $config);
        $this->assertArrayHasKey('filters', $config);
        $this->assertArrayHasKey('actions', $config);
        $this->assertArrayHasKey('bulkActions', $config);
        $this->assertArrayHasKey('defaultSort', $config);
        $this->assertArrayHasKey('perPage', $config);
        $this->assertArrayHasKey('searchable', $config);
        $this->assertArrayHasKey('searchPlaceholder', $config);
    }

    /**
     * Test table apply search with empty term
     */
    public function test_table_apply_search_with_empty_term(): void
    {
        $table = Table::make();
        $builder = $this->createMock(Builder::class);

        $result = $table->applySearch($builder, '');

        $this->assertSame($builder, $result);
    }

    /**
     * Test table apply search when searchable is false
     */
    public function test_table_apply_search_when_not_searchable(): void
    {
        $table = Table::make()->searchable(false);
        $builder = $this->createMock(Builder::class);

        $result = $table->applySearch($builder, 'test');

        $this->assertSame($builder, $result);
    }

    /**
     * Test table apply filters empty
     */
    public function test_table_apply_filters_empty(): void
    {
        $table = new Table();
        $builder = $this->createMock(Builder::class);

        $result = $table->applyFilters($builder, []);

        $this->assertSame($builder, $result);
    }

    /**
     * Test multiple table instances independence
     */
    public function test_multiple_table_instances(): void
    {
        $table1 = Table::make()->perPage(25);
        $table2 = Table::make()->perPage(50);

        $this->assertEquals(25, $table1->getPerPage());
        $this->assertEquals(50, $table2->getPerPage());
    }

    /**
     * Test table with per page different values
     */
    public function test_table_per_page_different_values(): void
    {
        foreach ([10, 25, 50, 100] as $perPage) {
            $table = Table::make()->perPage($perPage);
            $this->assertEquals($perPage, $table->getPerPage());
        }
    }

    /**
     * Test table columns method replaces previous
     */
    public function test_table_columns_replaces_previous(): void
    {
        $table = new Table();
        $col1 = $this->createMock(\stdClass::class);
        $col2 = $this->createMock(\stdClass::class);

        $table->columns([$col1]);
        $this->assertCount(1, $table->getColumns());

        $table->columns([$col2]);
        $this->assertCount(1, $table->getColumns());
        $this->assertSame($col2, $table->getColumns()[0]);
    }

    /**
     * Test table method visibility
     */
    public function test_table_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(Table::class);

        $publicMethods = [
            'make',
            'columns',
            'addColumn',
            'filters',
            'actions',
            'bulkActions',
            'defaultSort',
            'perPage',
            'searchable',
            'searchPlaceholder',
            'get',
            'getColumns',
            'getFilters',
            'getActions',
            'getBulkActions',
            'getDefaultSort',
            'getPerPage',
            'getSearchPlaceholder',
            'isSearchable',
            'hasBulkActions',
            'applyFilters',
            'applySearch'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Table should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test table namespace
     */
    public function test_table_namespace(): void
    {
        $reflection = new \ReflectionClass(Table::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test table class name
     */
    public function test_table_class_name(): void
    {
        $reflection = new \ReflectionClass(Table::class);
        $this->assertEquals('Table', $reflection->getShortName());
    }

    /**
     * Test table with special characters in search placeholder
     */
    public function test_table_search_placeholder_with_special_chars(): void
    {
        $table = Table::make()->searchPlaceholder('Search & filter...');
        $this->assertEquals('Search & filter...', $table->getSearchPlaceholder());
    }

    /**
     * Test table default sort with custom direction
     */
    public function test_table_default_sort_custom_direction(): void
    {
        $table = Table::make()->defaultSort('name', 'asc');
        $sort = $table->getDefaultSort();

        $this->assertEquals('name', $sort[0]);
        $this->assertEquals('asc', $sort[1]);
    }

    /**
     * Test table add column multiple
     */
    public function test_table_add_column_multiple(): void
    {
        $table = Table::make();
        $col1 = $this->createMock(\stdClass::class);
        $col2 = $this->createMock(\stdClass::class);

        $table->addColumn($col1)->addColumn($col2);

        $this->assertCount(2, $table->getColumns());
    }

    /**
     * Test table filters method replaces previous
     */
    public function test_table_filters_replaces_previous(): void
    {
        $table = new Table();
        $filter1 = $this->createMock(\stdClass::class);
        $filter2 = $this->createMock(\stdClass::class);

        $table->filters([$filter1]);
        $this->assertCount(1, $table->getFilters());

        $table->filters([$filter2]);
        $this->assertCount(1, $table->getFilters());
    }

    /**
     * Test table get returns correct data types
     */
    public function test_table_get_returns_correct_types(): void
    {
        $table = Table::make()
            ->perPage(50)
            ->searchable(true)
            ->searchPlaceholder('Test');

        $config = $table->get();

        $this->assertIsArray($config['columns']);
        $this->assertIsArray($config['filters']);
        $this->assertIsArray($config['actions']);
        $this->assertIsArray($config['bulkActions']);
        $this->assertIsInt($config['perPage']);
        $this->assertIsBool($config['searchable']);
        $this->assertIsString($config['searchPlaceholder']);
    }

    /**
     * Test table add column to empty columns
     */
    public function test_table_add_column_to_empty(): void
    {
        $table = new Table();
        $column = $this->createMock(\stdClass::class);

        $table->addColumn($column);

        $this->assertCount(1, $table->getColumns());
        $this->assertSame($column, $table->getColumns()[0]);
    }
}
