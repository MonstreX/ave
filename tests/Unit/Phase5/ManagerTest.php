<?php

namespace Monstrex\Ave\Tests\Unit\Phase5;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Manager\ResourceManager;
use Monstrex\Ave\Core\Manager\PageManager;
use Monstrex\Ave\Core\Discovery\ResourceDiscovery;
use Monstrex\Ave\Core\Discovery\PageDiscovery;
use Monstrex\Ave\Core\Registry\ResourceRegistry;
use Monstrex\Ave\Core\Registry\PageRegistry;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Page;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Form;

class ManagerTest extends TestCase
{
    public function test_resource_manager_can_be_created()
    {
        $manager = new ResourceManager();
        $this->assertInstanceOf(ResourceManager::class, $manager);
    }

    public function test_resource_manager_with_dependencies()
    {
        $discovery = new ResourceDiscovery();
        $registry = new ResourceRegistry();
        $manager = new ResourceManager($discovery, $registry);

        $this->assertInstanceOf(ResourceManager::class, $manager);
    }

    public function test_resource_manager_add_discovery_path()
    {
        $manager = new ResourceManager();
        $result = $manager->addDiscoveryPath(__DIR__);

        $this->assertInstanceOf(ResourceManager::class, $result);
    }

    public function test_resource_manager_register()
    {
        $manager = new ResourceManager();
        $manager->register('users', RegistryTestResource::class);

        $this->assertTrue($manager->has('users'));
    }

    public function test_resource_manager_get()
    {
        $manager = new ResourceManager();
        $manager->register('users', RegistryTestResource::class);

        $this->assertEquals(RegistryTestResource::class, $manager->get('users'));
    }

    public function test_resource_manager_get_instance()
    {
        $manager = new ResourceManager();
        $manager->register('users', RegistryTestResource::class);

        $instance = $manager->getInstance('users');
        $this->assertInstanceOf(RegistryTestResource::class, $instance);
    }

    public function test_resource_manager_all()
    {
        $manager = new ResourceManager();
        $manager->register('users', RegistryTestResource::class);

        $all = $manager->all();
        $this->assertArrayHasKey('users', $all);
    }

    public function test_resource_manager_all_instances()
    {
        $manager = new ResourceManager();
        $manager->register('users', RegistryTestResource::class);

        $instances = $manager->allInstances();
        $this->assertArrayHasKey('users', $instances);
        $this->assertInstanceOf(RegistryTestResource::class, $instances['users']);
    }

    public function test_resource_manager_count()
    {
        $manager = new ResourceManager();
        $manager->register('users', RegistryTestResource::class);

        $this->assertEquals(1, $manager->count());
    }

    public function test_resource_manager_get_discovery()
    {
        $manager = new ResourceManager();
        $discovery = $manager->getDiscovery();

        $this->assertInstanceOf(ResourceDiscovery::class, $discovery);
    }

    public function test_resource_manager_get_registry()
    {
        $manager = new ResourceManager();
        $registry = $manager->getRegistry();

        $this->assertInstanceOf(ResourceRegistry::class, $registry);
    }

    public function test_page_manager_can_be_created()
    {
        $manager = new PageManager();
        $this->assertInstanceOf(PageManager::class, $manager);
    }

    public function test_page_manager_with_dependencies()
    {
        $discovery = new PageDiscovery();
        $registry = new PageRegistry();
        $manager = new PageManager($discovery, $registry);

        $this->assertInstanceOf(PageManager::class, $manager);
    }

    public function test_page_manager_register()
    {
        $manager = new PageManager();
        $manager->register('dashboard', RegistryTestPage::class);

        $this->assertTrue($manager->has('dashboard'));
    }

    public function test_page_manager_get_instance()
    {
        $manager = new PageManager();
        $manager->register('dashboard', RegistryTestPage::class);

        $instance = $manager->getInstance('dashboard');
        $this->assertInstanceOf(RegistryTestPage::class, $instance);
    }

    public function test_page_manager_count()
    {
        $manager = new PageManager();
        $manager->register('dashboard', RegistryTestPage::class);

        $this->assertEquals(1, $manager->count());
    }
}

class ManagerTestResource extends Resource
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

class ManagerTestPage extends Page
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

class RegistryTestResource extends ManagerTestResource {}

class RegistryTestPage extends ManagerTestPage {}
