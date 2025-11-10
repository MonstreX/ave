<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Discovery\AdminResourceDiscovery;
use Monstrex\Ave\Core\Registry\ResourceRegistry;

/**
 * ResourceManagerTest - Unit tests for ResourceManager class.
 *
 * Tests the resource management facade which provides:
 * - Resource discovery and registration
 * - Resource retrieval by slug
 * - Resource instance creation
 * - Registry and discovery access
 */
class ResourceManagerTest extends TestCase
{
    private AdminResourceDiscovery $discovery;
    private ResourceRegistry $registry;
    private ResourceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discovery = $this->createMock(AdminResourceDiscovery::class);
        $this->registry = $this->createMock(ResourceRegistry::class);
        $this->manager = new ResourceManager($this->discovery, $this->registry);
    }

    /**
     * Test resource manager can be instantiated
     */
    public function test_resource_manager_can_be_instantiated(): void
    {
        $manager = new ResourceManager($this->discovery, $this->registry);
        $this->assertInstanceOf(ResourceManager::class, $manager);
    }

    /**
     * Test resource manager add discovery path is fluent
     */
    public function test_resource_manager_add_discovery_path_is_fluent(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();

        $result = $this->manager->addDiscoveryPath('/app/Resources');

        $this->assertInstanceOf(ResourceManager::class, $result);
        $this->assertSame($this->manager, $result);
    }

    /**
     * Test resource manager discover is fluent
     */
    public function test_resource_manager_discover_is_fluent(): void
    {
        $this->discovery->method('discover')->willReturn([]);
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager->discover();

        $this->assertInstanceOf(ResourceManager::class, $result);
        $this->assertSame($this->manager, $result);
    }

    /**
     * Test resource manager register is fluent
     */
    public function test_resource_manager_register_is_fluent(): void
    {
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager->register('ArticleResource');

        $this->assertInstanceOf(ResourceManager::class, $result);
        $this->assertSame($this->manager, $result);
    }

    /**
     * Test resource manager fluent interface chaining
     */
    public function test_resource_manager_fluent_interface_chaining(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();
        $this->discovery->method('discover')->willReturn([]);
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager
            ->addDiscoveryPath('/resources')
            ->discover()
            ->register('TestResource');

        $this->assertInstanceOf(ResourceManager::class, $result);
    }

    /**
     * Test resource manager resource returns resource class
     */
    public function test_resource_manager_resource_returns_class(): void
    {
        $this->registry->method('get')->with('articles')->willReturn('ArticleResource');

        $class = $this->manager->resource('articles');

        $this->assertEquals('ArticleResource', $class);
    }

    /**
     * Test resource manager resource returns null when not found
     */
    public function test_resource_manager_resource_returns_null(): void
    {
        $this->registry->method('get')->with('missing')->willReturn(null);

        $result = $this->manager->resource('missing');

        $this->assertNull($result);
    }

    /**
     * Test resource manager instance creates resource
     */
    public function test_resource_manager_instance_creates_resource(): void
    {
        $testResource = new class extends Resource {
            public static ?string $slug = 'articles';
        };

        $this->registry->method('get')->with('articles')->willReturn(get_class($testResource));

        $instance = $this->manager->instance('articles');

        $this->assertInstanceOf(Resource::class, $instance);
    }

    /**
     * Test resource manager instance returns null when not found
     */
    public function test_resource_manager_instance_returns_null(): void
    {
        $this->registry->method('get')->with('missing')->willReturn(null);

        $result = $this->manager->instance('missing');

        $this->assertNull($result);
    }

    /**
     * Test resource manager has returns true
     */
    public function test_resource_manager_has_returns_true(): void
    {
        $this->registry->method('has')->with('articles')->willReturn(true);

        $result = $this->manager->has('articles');

        $this->assertTrue($result);
    }

    /**
     * Test resource manager has returns false
     */
    public function test_resource_manager_has_returns_false(): void
    {
        $this->registry->method('has')->with('missing')->willReturn(false);

        $result = $this->manager->has('missing');

        $this->assertFalse($result);
    }

    /**
     * Test resource manager all returns resources
     */
    public function test_resource_manager_all_returns_resources(): void
    {
        $resources = ['articles' => 'ArticleResource', 'users' => 'UserResource'];
        $this->registry->method('all')->willReturn($resources);

        $result = $this->manager->all();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($resources, $result);
    }

    /**
     * Test resource manager all returns empty array
     */
    public function test_resource_manager_all_returns_empty(): void
    {
        $this->registry->method('all')->willReturn([]);

        $result = $this->manager->all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test resource manager count returns count
     */
    public function test_resource_manager_count_returns_count(): void
    {
        $this->registry->method('count')->willReturn(5);

        $result = $this->manager->count();

        $this->assertEquals(5, $result);
    }

    /**
     * Test resource manager count returns zero
     */
    public function test_resource_manager_count_returns_zero(): void
    {
        $this->registry->method('count')->willReturn(0);

        $result = $this->manager->count();

        $this->assertEquals(0, $result);
    }

    /**
     * Test resource manager registry returns instance
     */
    public function test_resource_manager_registry_returns_instance(): void
    {
        $result = $this->manager->registry();

        $this->assertSame($this->registry, $result);
    }

    /**
     * Test resource manager discovery returns instance
     */
    public function test_resource_manager_discovery_returns_instance(): void
    {
        $result = $this->manager->discovery();

        $this->assertSame($this->discovery, $result);
    }

    /**
     * Test resource manager with multiple discovery paths
     */
    public function test_resource_manager_multiple_discovery_paths(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();

        $result = $this->manager
            ->addDiscoveryPath('/path1')
            ->addDiscoveryPath('/path2');

        $this->assertInstanceOf(ResourceManager::class, $result);
    }

    /**
     * Test resource manager discover with multiple resources
     */
    public function test_resource_manager_discover_multiple(): void
    {
        $resources = [
            'articles' => 'App\Resources\ArticleResource',
            'users' => 'App\Resources\UserResource',
            'posts' => 'App\Resources\PostResource'
        ];

        $this->discovery->method('discover')->willReturn($resources);
        $this->registry->method('register')->willReturnSelf();

        $this->manager->discover();

        $this->assertInstanceOf(ResourceManager::class, $this->manager);
    }

    /**
     * Test resource manager method visibility
     */
    public function test_resource_manager_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(ResourceManager::class);

        $publicMethods = [
            'addDiscoveryPath',
            'discover',
            'register',
            'resource',
            'instance',
            'has',
            'all',
            'count',
            'registry',
            'discovery'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "ResourceManager should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test resource manager namespace
     */
    public function test_resource_manager_namespace(): void
    {
        $reflection = new \ReflectionClass(ResourceManager::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test resource manager constructor parameters
     */
    public function test_resource_manager_constructor_parameters(): void
    {
        $reflection = new \ReflectionClass(ResourceManager::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);
    }

    /**
     * Test resource manager resource with empty slug
     */
    public function test_resource_manager_resource_with_empty_slug(): void
    {
        $this->registry->method('get')->with('')->willReturn(null);

        $result = $this->manager->resource('');

        $this->assertNull($result);
    }

    /**
     * Test resource manager instance with empty slug
     */
    public function test_resource_manager_instance_with_empty_slug(): void
    {
        $this->registry->method('get')->with('')->willReturn(null);

        $result = $this->manager->instance('');

        $this->assertNull($result);
    }

    /**
     * Test resource manager has with empty slug
     */
    public function test_resource_manager_has_with_empty_slug(): void
    {
        $this->registry->method('has')->with('')->willReturn(false);

        $result = $this->manager->has('');

        $this->assertFalse($result);
    }

    /**
     * Test resource manager all returns array type
     */
    public function test_resource_manager_all_returns_array_type(): void
    {
        $this->registry->method('all')->willReturn(['test' => 'TestResource']);

        $result = $this->manager->all();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test', $result);
    }

    /**
     * Test resource manager count type
     */
    public function test_resource_manager_count_returns_int(): void
    {
        $this->registry->method('count')->willReturn(10);

        $result = $this->manager->count();

        $this->assertIsInt($result);
    }

    /**
     * Test resource manager register with custom slug
     */
    public function test_resource_manager_register_with_custom_slug(): void
    {
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager->register('ArticleResource', 'posts');

        $this->assertInstanceOf(ResourceManager::class, $result);
    }

    /**
     * Test resource manager register without slug
     */
    public function test_resource_manager_register_without_slug(): void
    {
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager->register('ArticleResource');

        $this->assertInstanceOf(ResourceManager::class, $result);
    }

    /**
     * Test resource manager with discovery path containing special characters
     */
    public function test_resource_manager_discovery_path_special_chars(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();

        $result = $this->manager->addDiscoveryPath('/app/Resources-v2');

        $this->assertInstanceOf(ResourceManager::class, $result);
    }

    /**
     * Test resource manager with resource class name containing namespace
     */
    public function test_resource_manager_resource_with_namespace(): void
    {
        $className = 'App\\Resources\\Articles\\ArticleResource';
        $this->registry->method('get')->with('articles')->willReturn($className);

        $result = $this->manager->resource('articles');

        $this->assertEquals($className, $result);
    }

    /**
     * Test resource manager count with different values
     */
    public function test_resource_manager_count_with_zero(): void
    {
        $registryMock = $this->createMock(ResourceRegistry::class);
        $registryMock->method('count')->willReturn(0);

        $manager = new ResourceManager($this->discovery, $registryMock);
        $this->assertEquals(0, $manager->count());
    }
}
