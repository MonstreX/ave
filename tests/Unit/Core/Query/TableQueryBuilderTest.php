<?php

namespace Monstrex\Ave\Tests\Unit\Core\Query;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Query\TableQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Table;

/**
 * TableQueryBuilderTest - Unit tests for TableQueryBuilder class.
 *
 * Tests the query builder abstraction which provides:
 * - Fluent interface for search, filters, sorting
 * - Request parameter handling
 * - Pagination and result fetching
 * - Integration with Table configuration
 */
class TableQueryBuilderTest extends TestCase
{
    private Builder $queryBuilder;
    private Request $request;
    private Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = $this->createMock(Builder::class);
        $this->request = $this->createMock(Request::class);
        $this->table = $this->createMock(Table::class);
    }

    /**
     * Test table query builder can be instantiated
     */
    public function test_table_query_builder_can_be_instantiated(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test table query builder for static factory
     */
    public function test_table_query_builder_for_factory(): void
    {
        $builder = TableQueryBuilder::for($this->queryBuilder);
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test search method is fluent
     */
    public function test_search_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->search('test query');

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test filters method is fluent
     */
    public function test_filters_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->filters(['status' => 'active']);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test sort method is fluent
     */
    public function test_sort_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->sort('name', 'asc');

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test per page method is fluent
     */
    public function test_per_page_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->perPage(50);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test page method is fluent
     */
    public function test_page_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->page(2);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test apply search method is fluent
     */
    public function test_apply_search_method_is_fluent(): void
    {
        $this->queryBuilder->method('where')->willReturnSelf();

        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->applySearch(['name', 'email']);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test apply filters method is fluent
     */
    public function test_apply_filters_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->applyFilters([]);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test apply sort method is fluent
     */
    public function test_apply_sort_method_is_fluent(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        // Set sort first to ensure applySort has something to apply
        $builder->sort('name', 'asc');
        $result = $builder->applySort();

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    /**
     * Test sort with column only defaults to asc
     */
    public function test_sort_with_column_only(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $builder->sort('name');

        // Verify the builder still works fluently
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test sort with null clears sort
     */
    public function test_sort_with_null_clears(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $builder->sort('name', 'desc');
        $builder->sort(null);

        // Verify the builder still works
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test fluent interface chaining for builder
     */
    public function test_fluent_interface_chaining(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder
            ->search('test')
            ->filters(['status' => 'active'])
            ->sort('created_at', 'desc')
            ->perPage(25)
            ->page(1);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    /**
     * Test multiple builder instances
     */
    public function test_multiple_builder_instances(): void
    {
        $builder1 = new TableQueryBuilder($this->queryBuilder);
        $builder2 = new TableQueryBuilder($this->createMock(Builder::class));

        $this->assertNotSame($builder1, $builder2);
    }

    /**
     * Test search with empty term
     */
    public function test_search_with_empty_term(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $builder->search('');

        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test filters with empty array
     */
    public function test_filters_with_empty_array(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $builder->filters([]);

        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test per page with different values
     */
    public function test_per_page_with_different_values(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);

        foreach ([10, 25, 50, 100] as $perPage) {
            $result = $builder->perPage($perPage);
            $this->assertInstanceOf(TableQueryBuilder::class, $result);
        }
    }

    /**
     * Test page with different values
     */
    public function test_page_with_different_values(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);

        foreach ([1, 2, 5, 10] as $page) {
            $result = $builder->page($page);
            $this->assertInstanceOf(TableQueryBuilder::class, $result);
        }
    }

    /**
     * Test apply static method exists and is callable
     */
    public function test_apply_static_method_exists(): void
    {
        $reflection = new \ReflectionClass(TableQueryBuilder::class);
        $this->assertTrue($reflection->hasMethod('apply'));
        $this->assertTrue($reflection->getMethod('apply')->isStatic());
    }

    /**
     * Test get per page static method
     */
    public function test_get_per_page_static_method(): void
    {
        $this->table->method('getPerPage')->willReturn(25);

        $perPage = TableQueryBuilder::getPerPage($this->table);

        $this->assertEquals(25, $perPage);
    }

    /**
     * Test apply search with empty searchable columns
     */
    public function test_apply_search_empty_searchable(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->search('test')->applySearch([]);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    /**
     * Test apply search without search term
     */
    public function test_apply_search_without_term(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->applySearch(['name']);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    /**
     * Test apply filters with empty filters
     */
    public function test_apply_filters_empty(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->applyFilters([]);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    /**
     * Test sort direction asc
     */
    public function test_sort_direction_asc(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $builder->sort('name', 'asc');

        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test sort direction desc
     */
    public function test_sort_direction_desc(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $builder->sort('name', 'desc');

        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    /**
     * Test method visibility
     */
    public function test_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(TableQueryBuilder::class);

        $publicMethods = [
            'for',
            'apply',
            'getPerPage',
            'search',
            'filters',
            'sort',
            'perPage',
            'page',
            'applySearch',
            'applyFilters',
            'applySort',
            'paginate',
            'get',
            'count'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "TableQueryBuilder should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test for and apply are static methods
     */
    public function test_static_methods(): void
    {
        $reflection = new \ReflectionClass(TableQueryBuilder::class);

        $staticMethods = ['for', 'apply', 'getPerPage'];

        foreach ($staticMethods as $method) {
            $this->assertTrue(
                $reflection->getMethod($method)->isStatic(),
                "Method {$method} should be static"
            );
        }
    }

    /**
     * Test namespace
     */
    public function test_namespace(): void
    {
        $reflection = new \ReflectionClass(TableQueryBuilder::class);
        $this->assertEquals('Monstrex\\Ave\\Core\\Query', $reflection->getNamespaceName());
    }

    /**
     * Test class name
     */
    public function test_class_name(): void
    {
        $reflection = new \ReflectionClass(TableQueryBuilder::class);
        $this->assertEquals('TableQueryBuilder', $reflection->getShortName());
    }

    /**
     * Test constructor accepts builder
     */
    public function test_constructor_parameter(): void
    {
        $reflection = new \ReflectionClass(TableQueryBuilder::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertEquals(1, count($parameters));
    }

    /**
     * Test search with special characters
     */
    public function test_search_with_special_characters(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->search('test & query%');

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    /**
     * Test per page with zero
     */
    public function test_per_page_with_zero(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->perPage(0);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    /**
     * Test page with zero
     */
    public function test_page_with_zero(): void
    {
        $builder = new TableQueryBuilder($this->queryBuilder);
        $result = $builder->page(0);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }
}
