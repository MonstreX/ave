<?php

namespace Monstrex\Ave\Tests\Unit\Phase6;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Form;
use Mockery;

class PersistenceTest extends TestCase
{
    public function test_resource_persistence_can_be_created()
    {
        $resource = new TestPersistenceResource();
        $persistence = new ResourcePersistence($resource);

        $this->assertInstanceOf(ResourcePersistence::class, $persistence);
    }

    public function test_resource_persistence_with_mock_model()
    {
        $resource = new TestPersistenceResource();
        $persistence = new ResourcePersistence($resource);

        $this->assertInstanceOf(ResourcePersistence::class, $persistence);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}

class TestPersistenceResource extends Resource
{
    public static ?string $model = \Illuminate\Database\Eloquent\Model::class;

    public static function table($ctx = null): Table
    {
        return Table::make();
    }

    public static function form($ctx = null): Form
    {
        return Form::make();
    }
}
