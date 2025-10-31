<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Form;

class ResourceTest extends TestCase
{
    protected TestResource $resource;

    protected function setUp(): void
    {
        $this->resource = new TestResource();
    }

    public function test_resource_can_be_instantiated()
    {
        $this->assertInstanceOf(Resource::class, $this->resource);
    }

    public function test_resource_has_static_properties()
    {
        $this->assertTrue(property_exists(Resource::class, 'model'));
        $this->assertTrue(property_exists(Resource::class, 'label'));
        $this->assertTrue(property_exists(Resource::class, 'slug'));
    }

    public function test_resource_get_slug()
    {
        $slug = TestResource::getSlug();
        $this->assertEquals('testresource', $slug);
    }

    public function test_resource_get_label()
    {
        $label = TestResource::getLabel();
        // TestResource has $label = 'Test Resource', so it should return that
        $this->assertEquals('Test Resource', $label);
    }

    public function test_resource_table_method_returns_table()
    {
        $table = TestResource::table(null);
        $this->assertInstanceOf(Table::class, $table);
    }

    public function test_resource_form_method_returns_form()
    {
        $form = TestResource::form(null);
        $this->assertInstanceOf(Form::class, $form);
    }

    public function test_resource_authorize_returns_true_without_policy()
    {
        $result = $this->resource->authorize('viewAny');
        $this->assertTrue($result);
    }
}

class TestResource extends Resource
{
    public static ?string $model = null;
    public static ?string $label = 'Test Resource';
    public static ?string $slug = null;
}
