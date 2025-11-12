<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use Monstrex\Ave\Core\Columns\Column;
use Monstrex\Ave\Core\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function test_make_creates_instance(): void
    {
        $this->assertInstanceOf(Table::class, Table::make());
    }

    public function test_columns_and_add_column_store_instances(): void
    {
        $columnA = $this->createMock(\stdClass::class);
        $columnB = $this->createMock(\stdClass::class);

        $table = Table::make()
            ->columns([$columnA])
            ->addColumn($columnB);

        $columns = $table->getColumns();

        $this->assertCount(2, $columns);
        $this->assertSame([$columnA, $columnB], $columns);
    }

    public function test_filters_can_be_assigned(): void
    {
        $filter = $this->createMock(\stdClass::class);

        $table = Table::make()->filters([$filter]);

        $this->assertSame([$filter], $table->getFilters());
    }

    public function test_default_sort_and_per_page_configuration(): void
    {
        $table = Table::make()
            ->defaultSort('created_at', 'desc')
            ->perPage(50);

        $this->assertSame(['created_at', 'desc'], $table->getDefaultSort());
        $this->assertSame(50, $table->getPerPage());
    }

    public function test_search_flags(): void
    {
        $table = Table::make()
            ->searchable(false)
            ->searchPlaceholder('Поиск записей');

        $this->assertFalse($table->isSearchable());
        $this->assertSame('Поиск записей', $table->getSearchPlaceholder());
    }

    public function test_get_exports_serializable_structure(): void
    {
        $column = $this->createConfiguredMock(DummyArrayable::class, [
            'toArray' => ['key' => 'name'],
        ]);
        $filter = $this->createConfiguredMock(DummyArrayable::class, [
            'toArray' => ['key' => 'status'],
        ]);

        $table = Table::make()
            ->columns([$column])
            ->filters([$filter])
            ->defaultSort('id', 'asc')
            ->perPage(15)
            ->searchPlaceholder('Search term');

        $payload = $table->get();

        $this->assertSame([['key' => 'name']], $payload['columns']);
        $this->assertSame([['key' => 'status']], $payload['filters']);
        $this->assertSame(['id', 'asc'], $payload['defaultSort']);
        $this->assertSame(15, $payload['perPage']);
        $this->assertTrue($payload['searchable']);
        $this->assertSame('Search term', $payload['searchPlaceholder']);
    }

    public function test_find_inline_column_returns_column(): void
    {
        $inlineColumn = Column::make('status')->inline('toggle');
        $table = Table::make()->columns([$inlineColumn]);

        $this->assertSame($inlineColumn, $table->findInlineColumn('status'));
        $this->assertNull($table->findInlineColumn('missing'));
    }
}

/**
 * Simple helper that mimics a column/filter object with toArray()
 */
abstract class DummyArrayable
{
    abstract public function toArray(): array;
}
