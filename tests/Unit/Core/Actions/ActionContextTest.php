<?php

namespace Monstrex\Ave\Tests\Unit\Core\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use PHPUnit\Framework\TestCase;

class ActionContextTest extends TestCase
{
    public function test_row_context(): void
    {
        $user = $this->createMock(Authenticatable::class);
        $model = $this->createMock(Model::class);

        $context = ActionContext::row('TestResource', $user, $model);

        $this->assertEquals('row', $context->mode());
        $this->assertEquals('TestResource', $context->resourceClass());
        $this->assertSame($user, $context->user());
        $this->assertSame($model, $context->model());
    }

    public function test_bulk_context(): void
    {
        $user = $this->createMock(Authenticatable::class);
        $model = $this->createMock(Model::class);
        $collection = new Collection([$model]);

        $context = ActionContext::bulk('TestResource', $user, $collection, [1, 2]);

        $this->assertEquals('bulk', $context->mode());
        $this->assertEquals([1, 2], $context->ids());
        $this->assertSame($collection, $context->models());
    }
}

