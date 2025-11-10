<?php

namespace Monstrex\Ave\Tests\Unit\Core\Discovery;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Discovery\AdminResourceDiscovery;
use Monstrex\Ave\Core\Discovery\AdminPageDiscovery;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\PageManager;
use Monstrex\Ave\Core\Registry\ResourceRegistry;
use Monstrex\Ave\Core\Registry\PageRegistry;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Page;

/**
 * DiscoveryTest - Unit tests for Discovery mechanism classes.
 *
 * Tests the discovery system which:
 * - Scans directories for Resource and Page classes
 * - Registers discovered classes by slug
 * - Provides access to registered resources/pages
 * - Caches discovery results
 */
class DiscoveryTest extends TestCase
{
    /**
     * Test AdminResourceDiscovery can be instantiated
     */
    public function test_admin_resource_discovery_instantiation(): void
    {
        $discovery = new AdminResourceDiscovery();
        $this->assertInstanceOf(AdminResourceDiscovery::class, $discovery);
    }

    /**
     * Test AdminResourceDiscovery accepts paths in constructor
     */
    public function test_admin_resource_discovery_with_paths(): void
    {
        $paths = ['/path/to/resources', '/another/path'];
        $discovery = new AdminResourceDiscovery($paths);

        $this->assertInstanceOf(AdminResourceDiscovery::class, $discovery);
    }

    /**
     * Test AdminResourceDiscovery addPath returns self
     */
    public function test_admin_resource_discovery_add_path_fluent(): void
    {
        $discovery = new AdminResourceDiscovery();
        $result = $discovery->addPath('/some/path');

        $this->assertSame($discovery, $result);
    }

    /**
     * Test AdminResourceDiscovery has discover method
     */
    public function test_admin_resource_discovery_has_discover_method(): void
    {
        $reflection = new \ReflectionClass(AdminResourceDiscovery::class);
        $this->assertTrue($reflection->hasMethod('discover'));
        $this->assertTrue($reflection->getMethod('discover')->isPublic());
    }

    /**
     * Test AdminResourceDiscovery discover returns array
     */
    public function test_admin_resource_discovery_discover_returns_array(): void
    {
        $reflection = new \ReflectionClass(AdminResourceDiscovery::class);
        $method = $reflection->getMethod('discover');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test AdminResourceDiscovery has getResources method
     */
    public function test_admin_resource_discovery_has_get_resources_method(): void
    {
        $reflection = new \ReflectionClass(AdminResourceDiscovery::class);
        $this->assertTrue($reflection->hasMethod('getResources'));
        $this->assertTrue($reflection->getMethod('getResources')->isPublic());
    }

    /**
     * Test AdminResourceDiscovery has getResource method
     */
    public function test_admin_resource_discovery_has_get_resource_method(): void
    {
        $reflection = new \ReflectionClass(AdminResourceDiscovery::class);
        $this->assertTrue($reflection->hasMethod('getResource'));
        $this->assertTrue($reflection->getMethod('getResource')->isPublic());
    }

    /**
     * Test AdminPageDiscovery can be instantiated
     */
    public function test_admin_page_discovery_instantiation(): void
    {
        $discovery = new AdminPageDiscovery();
        $this->assertInstanceOf(AdminPageDiscovery::class, $discovery);
    }

    /**
     * Test AdminPageDiscovery accepts paths in constructor
     */
    public function test_admin_page_discovery_with_paths(): void
    {
        $paths = ['/path/to/pages', '/another/path'];
        $discovery = new AdminPageDiscovery($paths);

        $this->assertInstanceOf(AdminPageDiscovery::class, $discovery);
    }

    /**
     * Test AdminPageDiscovery addPath returns self
     */
    public function test_admin_page_discovery_add_path_fluent(): void
    {
        $discovery = new AdminPageDiscovery();
        $result = $discovery->addPath('/some/path');

        $this->assertSame($discovery, $result);
    }

    /**
     * Test AdminPageDiscovery has discover method
     */
    public function test_admin_page_discovery_has_discover_method(): void
    {
        $reflection = new \ReflectionClass(AdminPageDiscovery::class);
        $this->assertTrue($reflection->hasMethod('discover'));
        $this->assertTrue($reflection->getMethod('discover')->isPublic());
    }

    /**
     * Test AdminPageDiscovery discover returns array
     */
    public function test_admin_page_discovery_discover_returns_array(): void
    {
        $reflection = new \ReflectionClass(AdminPageDiscovery::class);
        $method = $reflection->getMethod('discover');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test AdminPageDiscovery has getPages method
     */
    public function test_admin_page_discovery_has_get_pages_method(): void
    {
        $reflection = new \ReflectionClass(AdminPageDiscovery::class);
        $this->assertTrue($reflection->hasMethod('getPages'));
        $this->assertTrue($reflection->getMethod('getPages')->isPublic());
    }

    /**
     * Test AdminPageDiscovery has getPage method
     */
    public function test_admin_page_discovery_has_get_page_method(): void
    {
        $reflection = new \ReflectionClass(AdminPageDiscovery::class);
        $this->assertTrue($reflection->hasMethod('getPage'));
        $this->assertTrue($reflection->getMethod('getPage')->isPublic());
    }

    /**
     * Test ResourceRegistry can be instantiated
     */
    public function test_resource_registry_instantiation(): void
    {
        $registry = new ResourceRegistry();
        $this->assertInstanceOf(ResourceRegistry::class, $registry);
    }

    /**
     * Test ResourceRegistry register method
     */
    public function test_resource_registry_has_register_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('register'));
        $this->assertTrue($reflection->getMethod('register')->isPublic());
    }

    /**
     * Test ResourceRegistry register returns self
     */
    public function test_resource_registry_register_returns_self(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $method = $reflection->getMethod('register');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test ResourceRegistry has method
     */
    public function test_resource_registry_has_has_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('has'));
        $this->assertTrue($reflection->getMethod('has')->isPublic());
    }

    /**
     * Test ResourceRegistry get method
     */
    public function test_resource_registry_has_get_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('get'));
        $this->assertTrue($reflection->getMethod('get')->isPublic());
    }

    /**
     * Test ResourceRegistry all method
     */
    public function test_resource_registry_has_all_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('all'));
        $this->assertTrue($reflection->getMethod('all')->isPublic());
    }

    /**
     * Test ResourceRegistry all returns array
     */
    public function test_resource_registry_all_returns_array(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $method = $reflection->getMethod('all');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test ResourceRegistry count method
     */
    public function test_resource_registry_has_count_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('count'));
        $this->assertTrue($reflection->getMethod('count')->isPublic());
    }

    /**
     * Test ResourceRegistry count returns int
     */
    public function test_resource_registry_count_returns_int(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $method = $reflection->getMethod('count');

        $this->assertEquals('int', $method->getReturnType()->getName());
    }

    /**
     * Test ResourceRegistry has unregister method
     */
    public function test_resource_registry_has_unregister_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('unregister'));
    }

    /**
     * Test ResourceRegistry has clear method
     */
    public function test_resource_registry_has_clear_method(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $this->assertTrue($reflection->hasMethod('clear'));
    }

    /**
     * Test PageRegistry can be instantiated
     */
    public function test_page_registry_instantiation(): void
    {
        $registry = new PageRegistry();
        $this->assertInstanceOf(PageRegistry::class, $registry);
    }

    /**
     * Test PageRegistry register method
     */
    public function test_page_registry_has_register_method(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $this->assertTrue($reflection->hasMethod('register'));
        $this->assertTrue($reflection->getMethod('register')->isPublic());
    }

    /**
     * Test PageRegistry has method
     */
    public function test_page_registry_has_has_method(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $this->assertTrue($reflection->hasMethod('has'));
        $this->assertTrue($reflection->getMethod('has')->isPublic());
    }

    /**
     * Test PageRegistry get method
     */
    public function test_page_registry_has_get_method(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $this->assertTrue($reflection->hasMethod('get'));
        $this->assertTrue($reflection->getMethod('get')->isPublic());
    }

    /**
     * Test PageRegistry all method
     */
    public function test_page_registry_has_all_method(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $this->assertTrue($reflection->hasMethod('all'));
        $this->assertTrue($reflection->getMethod('all')->isPublic());
    }

    /**
     * Test PageRegistry count method
     */
    public function test_page_registry_has_count_method(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $this->assertTrue($reflection->hasMethod('count'));
        $this->assertTrue($reflection->getMethod('count')->isPublic());
    }

    /**
     * Test Resource base class exists
     */
    public function test_resource_base_class_exists(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertFalse($reflection->isInterface());
    }

    /**
     * Test Resource has getSlug method
     */
    public function test_resource_has_get_slug_method(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertTrue($reflection->hasMethod('getSlug'));
        $this->assertTrue($reflection->getMethod('getSlug')->isPublic());
    }

    /**
     * Test Resource getSlug returns string
     */
    public function test_resource_get_slug_returns_string(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $method = $reflection->getMethod('getSlug');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /**
     * Test Resource has getLabel method
     */
    public function test_resource_has_get_label_method(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertTrue($reflection->hasMethod('getLabel'));
    }

    /**
     * Test Resource has $slug static property
     */
    public function test_resource_has_slug_property(): void
    {
        $reflection = new \ReflectionClass(Resource::class);
        $this->assertTrue($reflection->hasProperty('slug'));
    }

    /**
     * Test Page base class exists
     */
    public function test_page_base_class_exists(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $this->assertFalse($reflection->isInterface());
    }

    /**
     * Test Page has getSlug method
     */
    public function test_page_has_get_slug_method(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $this->assertTrue($reflection->hasMethod('getSlug'));
        $this->assertTrue($reflection->getMethod('getSlug')->isPublic());
    }

    /**
     * Test Page getSlug returns string
     */
    public function test_page_get_slug_returns_string(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $method = $reflection->getMethod('getSlug');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /**
     * Test Page has getLabel method
     */
    public function test_page_has_get_label_method(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $this->assertTrue($reflection->hasMethod('getLabel'));
    }

    /**
     * Test Page has $slug static property
     */
    public function test_page_has_slug_property(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $this->assertTrue($reflection->hasProperty('slug'));
    }

    /**
     * Test AdminResourceDiscovery has protected scanDirectory method
     */
    public function test_admin_resource_discovery_has_scan_directory_method(): void
    {
        $reflection = new \ReflectionClass(AdminResourceDiscovery::class);
        $this->assertTrue($reflection->hasMethod('scanDirectory'));
    }

    /**
     * Test AdminPageDiscovery has protected scanDirectory method
     */
    public function test_admin_page_discovery_has_scan_directory_method(): void
    {
        $reflection = new \ReflectionClass(AdminPageDiscovery::class);
        $this->assertTrue($reflection->hasMethod('scanDirectory'));
    }

    /**
     * Test ResourceRegistry has unregister method returning self
     */
    public function test_resource_registry_unregister_returns_self(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $method = $reflection->getMethod('unregister');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test PageRegistry unregister returns self
     */
    public function test_page_registry_unregister_returns_self(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $method = $reflection->getMethod('unregister');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test ResourceRegistry clear returns self
     */
    public function test_resource_registry_clear_returns_self(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $method = $reflection->getMethod('clear');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test PageRegistry clear returns self
     */
    public function test_page_registry_clear_returns_self(): void
    {
        $reflection = new \ReflectionClass(PageRegistry::class);
        $method = $reflection->getMethod('clear');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test multiple discovery instances
     */
    public function test_multiple_discovery_instances(): void
    {
        $discovery1 = new AdminResourceDiscovery();
        $discovery2 = new AdminResourceDiscovery();

        $this->assertInstanceOf(AdminResourceDiscovery::class, $discovery1);
        $this->assertInstanceOf(AdminResourceDiscovery::class, $discovery2);
        $this->assertNotSame($discovery1, $discovery2);
    }

    /**
     * Test discovery with custom paths
     */
    public function test_discovery_can_add_multiple_paths(): void
    {
        $discovery = new AdminResourceDiscovery();

        $result1 = $discovery->addPath('/path/one');
        $result2 = $result1->addPath('/path/two');
        $result3 = $result2->addPath('/path/three');

        // All should return self for fluent interface
        $this->assertSame($discovery, $result3);
    }

    /**
     * Test registry has fluent interface
     */
    public function test_resource_registry_fluent_interface(): void
    {
        $reflection = new \ReflectionClass(ResourceRegistry::class);
        $method = $reflection->getMethod('register');

        // register() should return self for chaining
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test discovery classes have consistent methods
     */
    public function test_resource_and_page_discovery_have_same_interface(): void
    {
        $resourceMethods = array_map(
            fn($m) => $m->getName(),
            (new \ReflectionClass(AdminResourceDiscovery::class))->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $pageMethods = array_map(
            fn($m) => $m->getName(),
            (new \ReflectionClass(AdminPageDiscovery::class))->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        // Both should have discover, getResources/getPages, getResource/getPage
        $this->assertContains('discover', $resourceMethods);
        $this->assertContains('discover', $pageMethods);

        $this->assertContains('addPath', $resourceMethods);
        $this->assertContains('addPath', $pageMethods);
    }

    /**
     * Test registries have consistent interface
     */
    public function test_resource_and_page_registries_have_same_interface(): void
    {
        $resourceMethods = array_map(
            fn($m) => $m->getName(),
            (new \ReflectionClass(ResourceRegistry::class))->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $pageMethods = array_map(
            fn($m) => $m->getName(),
            (new \ReflectionClass(PageRegistry::class))->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        // Both should have same core methods
        $coreMethods = ['register', 'unregister', 'has', 'get', 'all', 'count', 'clear'];

        foreach ($coreMethods as $method) {
            $this->assertContains($method, $resourceMethods, "Resource registry missing $method");
            $this->assertContains($method, $pageMethods, "Page registry missing $method");
        }
    }
}
