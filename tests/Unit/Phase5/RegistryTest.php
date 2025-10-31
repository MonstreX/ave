<?php

namespace Monstrex\Ave\Tests\Unit\Phase5;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Registry\ResourceRegistry;
use Monstrex\Ave\Core\Registry\PageRegistry;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Page;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Form;

class RegistryTest extends TestCase
{
    public function test_resource_registry_can_be_created()
    {
        $registry = new ResourceRegistry();
        $this->assertInstanceOf(ResourceRegistry::class, $registry);
    }

    public function test_resource_registry_register()
    {
        $registry = new ResourceRegistry();
        $registry->register('users', TestResource::class);

        $this->assertTrue($registry->has('users'));
    }

    public function test_resource_registry_register_fluent_interface()
    {
        $registry = new ResourceRegistry();
        $result = $registry->register('users', TestResource::class);

        $this->assertInstanceOf(ResourceRegistry::class, $result);
    }

    public function test_resource_registry_get()
    {
        $registry = new ResourceRegistry();
        $registry->register('users', TestResource::class);

        $this->assertEquals(TestResource::class, $registry->get('users'));
    }

    public function test_resource_registry_get_nonexistent_returns_null()
    {
        $registry = new ResourceRegistry();
        $this->assertNull($registry->get('nonexistent'));
    }

    public function test_resource_registry_unregister()
    {
        $registry = new ResourceRegistry();
        $registry->register('users', TestResource::class);
        $registry->unregister('users');

        $this->assertFalse($registry->has('users'));
    }

    public function test_resource_registry_all()
    {
        $registry = new ResourceRegistry();
        $registry->register('users', TestResource::class);

        $all = $registry->all();
        $this->assertArrayHasKey('users', $all);
    }

    public function test_resource_registry_count()
    {
        $registry = new ResourceRegistry();
        $registry->register('users', TestResource::class);
        $registry->register('posts', TestResource::class);

        $this->assertEquals(2, $registry->count());
    }

    public function test_resource_registry_clear()
    {
        $registry = new ResourceRegistry();
        $registry->register('users', TestResource::class);
        $registry->clear();

        $this->assertEquals(0, $registry->count());
    }

    public function test_page_registry_can_be_created()
    {
        $registry = new PageRegistry();
        $this->assertInstanceOf(PageRegistry::class, $registry);
    }

    public function test_page_registry_register()
    {
        $registry = new PageRegistry();
        $registry->register('dashboard', TestPage::class);

        $this->assertTrue($registry->has('dashboard'));
    }

    public function test_page_registry_get()
    {
        $registry = new PageRegistry();
        $registry->register('dashboard', TestPage::class);

        $this->assertEquals(TestPage::class, $registry->get('dashboard'));
    }

    public function test_page_registry_count()
    {
        $registry = new PageRegistry();
        $registry->register('dashboard', TestPage::class);

        $this->assertEquals(1, $registry->count());
    }
}

class TestResource extends Resource
{
    public static ?string $model = null;

    public static function table($ctx = null): Table
    {
        return Table::make();
    }

    public static function form($ctx = null): Form
    {
        return Form::make();
    }
}

class TestPage extends Page
{
    public static function slug(): string
    {
        return 'test';
    }

    public static function label(): string
    {
        return 'Test';
    }

    public static function render($ctx = null): array
    {
        return [];
    }
}
