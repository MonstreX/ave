<?php

namespace Monstrex\Ave\Tests\Unit\Phase3;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Query\TableQueryBuilder;
use Mockery;

class TableQueryBuilderTest extends TestCase
{
    protected $queryMock;

    protected function setUp(): void
    {
        $this->queryMock = Mockery::mock('Illuminate\Database\Eloquent\Builder');
    }

    public function test_query_builder_creation()
    {
        $builder = TableQueryBuilder::for($this->queryMock);
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    public function test_query_builder_fluent_interface()
    {
        $this->queryMock->shouldReceive('where')->andReturnSelf();

        $result = TableQueryBuilder::for($this->queryMock)
            ->search('test')
            ->sort('name', 'asc')
            ->perPage(50);

        $this->assertInstanceOf(TableQueryBuilder::class, $result);
    }

    public function test_query_builder_search()
    {
        $this->queryMock->shouldReceive('where')->andReturnSelf();

        $builder = TableQueryBuilder::for($this->queryMock)
            ->search('john');

        $builder->applySearch(['name', 'email']);
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    public function test_query_builder_sort()
    {
        $this->queryMock->shouldReceive('orderBy')
            ->with('name', 'desc')
            ->andReturnSelf();

        $builder = TableQueryBuilder::for($this->queryMock)
            ->sort('name', 'desc');

        $builder->applySort();
        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    public function test_query_builder_per_page()
    {
        $builder = TableQueryBuilder::for($this->queryMock)
            ->perPage(100);

        $this->assertInstanceOf(TableQueryBuilder::class, $builder);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
