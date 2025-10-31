<?php

namespace Monstrex\Ave\Tests\Unit\Phase3;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Filters\SelectFilter;
use Monstrex\Ave\Core\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class FilterTest extends TestCase
{
    public function test_select_filter_creation()
    {
        $filter = SelectFilter::make('status')
            ->label('Status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive']);

        $this->assertEquals('status', $filter->key());
        $this->assertEquals('Status', $filter->getLabel());
    }

    public function test_select_filter_apply()
    {
        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('where')
            ->with('status', '=', 'active')
            ->andReturnSelf();

        $filter = SelectFilter::make('status');
        $result = $filter->apply($queryMock, 'active');

        $this->assertNotNull($result);
    }

    public function test_select_filter_multiple()
    {
        $filter = SelectFilter::make('roles')->multiple();
        $array = $filter->toArray();

        $this->assertTrue($array['multiple']);
    }

    public function test_date_filter_creation()
    {
        $filter = DateFilter::make('created_at')
            ->label('Created Date')
            ->format('Y-m-d');

        $this->assertEquals('created_at', $filter->key());
        $this->assertEquals('Created Date', $filter->getLabel());
    }

    public function test_date_filter_apply()
    {
        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('whereDate')
            ->with('created_at', '=', '2024-01-01')
            ->andReturnSelf();

        $filter = DateFilter::make('created_at');
        $result = $filter->apply($queryMock, '2024-01-01');

        $this->assertNotNull($result);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
