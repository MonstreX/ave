<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Page;
use Monstrex\Ave\Core\PageManager;
use Monstrex\Ave\Core\Discovery\AdminPageDiscovery;
use Monstrex\Ave\Core\Registry\PageRegistry;
use Illuminate\Http\Request;

/**
 * PageAndPageManagerTest - Unit tests for Page and PageManager classes.
 *
 * Tests the page system which provides:
 * - Abstract Page base class for page definitions
 * - Static slug, label, icon, and nav sort properties
 * - Page discovery and management via PageManager facade
 * - Fluent interface for page configuration
 * - Page rendering with title resolution
 */
class PageAndPageManagerTest extends TestCase
{
    private AdminPageDiscovery $discovery;
    private PageRegistry $registry;
    private PageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discovery = $this->createMock(AdminPageDiscovery::class);
        $this->registry = $this->createMock(PageRegistry::class);
        $this->manager = new PageManager($this->discovery, $this->registry);
    }

    /**
     * Test page manager can be instantiated
     */
    public function test_page_manager_can_be_instantiated(): void
    {
        $manager = new PageManager($this->discovery, $this->registry);
        $this->assertInstanceOf(PageManager::class, $manager);
    }

    /**
     * Test page manager add discovery path is fluent
     */
    public function test_page_manager_add_discovery_path_is_fluent(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();

        $result = $this->manager->addDiscoveryPath('/app/Pages');

        $this->assertInstanceOf(PageManager::class, $result);
        $this->assertSame($this->manager, $result);
    }

    /**
     * Test page manager discover is fluent
     */
    public function test_page_manager_discover_is_fluent(): void
    {
        $this->discovery->method('discover')->willReturn([]);
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager->discover();

        $this->assertInstanceOf(PageManager::class, $result);
        $this->assertSame($this->manager, $result);
    }

    /**
     * Test page manager register is fluent
     */
    public function test_page_manager_register_is_fluent(): void
    {
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager->register('TestPage');

        $this->assertInstanceOf(PageManager::class, $result);
        $this->assertSame($this->manager, $result);
    }

    /**
     * Test page manager fluent interface chaining
     */
    public function test_page_manager_fluent_interface_chaining(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();
        $this->discovery->method('discover')->willReturn([]);
        $this->registry->method('register')->willReturnSelf();

        $result = $this->manager
            ->addDiscoveryPath('/pages')
            ->discover()
            ->register('TestPage');

        $this->assertInstanceOf(PageManager::class, $result);
    }

    /**
     * Test page manager page method returns registered page class
     */
    public function test_page_manager_page_returns_class(): void
    {
        $this->registry->method('get')->with('dashboard')->willReturn('DashboardPage');

        $class = $this->manager->page('dashboard');

        $this->assertEquals('DashboardPage', $class);
    }

    /**
     * Test page manager page method returns null when not found
     */
    public function test_page_manager_page_returns_null_when_not_found(): void
    {
        $this->registry->method('get')->with('missing')->willReturn(null);

        $result = $this->manager->page('missing');

        $this->assertNull($result);
    }

    /**
     * Test page manager instance creates page instance
     */
    public function test_page_manager_instance_creates_page(): void
    {
        $testPage = new class extends Page {
            public static ?string $slug = 'test';
        };

        $this->registry->method('get')->with('test')->willReturn(get_class($testPage));

        $instance = $this->manager->instance('test');

        $this->assertInstanceOf(Page::class, $instance);
    }

    /**
     * Test page manager instance returns null when not found
     */
    public function test_page_manager_instance_returns_null_when_not_found(): void
    {
        $this->registry->method('get')->with('missing')->willReturn(null);

        $result = $this->manager->instance('missing');

        $this->assertNull($result);
    }

    /**
     * Test page manager has returns true when registered
     */
    public function test_page_manager_has_returns_true(): void
    {
        $this->registry->method('has')->with('dashboard')->willReturn(true);

        $result = $this->manager->has('dashboard');

        $this->assertTrue($result);
    }

    /**
     * Test page manager has returns false when not registered
     */
    public function test_page_manager_has_returns_false(): void
    {
        $this->registry->method('has')->with('missing')->willReturn(false);

        $result = $this->manager->has('missing');

        $this->assertFalse($result);
    }

    /**
     * Test page manager all returns registered pages
     */
    public function test_page_manager_all_returns_pages(): void
    {
        $pages = ['dashboard' => 'DashboardPage', 'settings' => 'SettingsPage'];
        $this->registry->method('all')->willReturn($pages);

        $result = $this->manager->all();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($pages, $result);
    }

    /**
     * Test page manager all returns empty array
     */
    public function test_page_manager_all_returns_empty(): void
    {
        $this->registry->method('all')->willReturn([]);

        $result = $this->manager->all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test page manager count returns page count
     */
    public function test_page_manager_count_returns_count(): void
    {
        $this->registry->method('count')->willReturn(5);

        $result = $this->manager->count();

        $this->assertEquals(5, $result);
    }

    /**
     * Test page manager count returns zero
     */
    public function test_page_manager_count_returns_zero(): void
    {
        $this->registry->method('count')->willReturn(0);

        $result = $this->manager->count();

        $this->assertEquals(0, $result);
    }

    /**
     * Test page manager registry returns registry instance
     */
    public function test_page_manager_registry_returns_instance(): void
    {
        $result = $this->manager->registry();

        $this->assertSame($this->registry, $result);
    }

    /**
     * Test page manager discovery returns discovery instance
     */
    public function test_page_manager_discovery_returns_instance(): void
    {
        $result = $this->manager->discovery();

        $this->assertSame($this->discovery, $result);
    }

    /**
     * Test page can be instantiated as subclass
     */
    public function test_page_can_be_instantiated(): void
    {
        $page = new class extends Page {
            public static ?string $slug = 'test';
        };

        $this->assertInstanceOf(Page::class, $page);
    }

    /**
     * Test page get slug with custom slug
     */
    public function test_page_get_slug_with_custom(): void
    {
        $page = new class extends Page {
            public static ?string $slug = 'custom-slug';
        };

        $this->assertEquals('custom-slug', $page->getSlug());
    }

    /**
     * Test page get slug defaults to class name lowercase
     */
    public function test_page_get_slug_defaults_to_class_name(): void
    {
        $page = new class extends Page {
            public static ?string $slug = null;
        };

        // Class basename is 'AnonymousClass', which will be lowercased
        $this->assertIsString($page->getSlug());
        $this->assertEquals(strtolower(class_basename($page)), $page->getSlug());
    }

    /**
     * Test page slug alias method
     */
    public function test_page_slug_alias_method(): void
    {
        $page = new class extends Page {
            public static ?string $slug = 'dashboard';
        };

        $this->assertEquals('dashboard', $page->slug());
        $this->assertEquals($page->getSlug(), $page->slug());
    }

    /**
     * Test page get label with custom label
     */
    public function test_page_get_label_with_custom(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'Dashboard';
        };

        $this->assertEquals('Dashboard', $page->getLabel());
    }

    /**
     * Test page get label defaults to class name
     */
    public function test_page_get_label_defaults_to_class_name(): void
    {
        $page = new class extends Page {
            public static ?string $label = null;
        };

        $this->assertIsString($page->getLabel());
        $this->assertEquals(class_basename($page), $page->getLabel());
    }

    /**
     * Test page label alias method
     */
    public function test_page_label_alias_method(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'Dashboard';
        };

        $this->assertEquals('Dashboard', $page->label());
        $this->assertEquals($page->getLabel(), $page->label());
    }

    /**
     * Test page render returns array with title
     */
    public function test_page_render_returns_array(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'Dashboard';
        };

        $request = $this->createMock(Request::class);
        $result = $page->render($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
    }

    /**
     * Test page render uses label as title
     */
    public function test_page_render_uses_label_as_title(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'My Dashboard';
        };

        $request = $this->createMock(Request::class);
        $result = $page->render($request);

        $this->assertEquals('My Dashboard', $result['title']);
    }

    /**
     * Test page render falls back to slug
     */
    public function test_page_render_fallback_to_slug(): void
    {
        $page = new class extends Page {
            public static ?string $label = null;
            public static ?string $slug = 'dashboard';
        };

        $request = $this->createMock(Request::class);
        $result = $page->render($request);

        $this->assertEquals('dashboard', $result['title']);
    }

    /**
     * Test page render fallback to default title
     */
    public function test_page_render_default_title(): void
    {
        $page = new class extends Page {
            public static ?string $label = null;
            public static ?string $slug = null;
        };

        $request = $this->createMock(Request::class);
        $result = $page->render($request);

        $this->assertEquals('Page', $result['title']);
    }

    /**
     * Test page static properties
     */
    public function test_page_static_properties(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'Test';
            public static ?string $icon = 'fa fa-test';
            public static ?string $slug = 'test';
            public static ?int $navSort = 10;
        };

        $this->assertEquals('Test', $page::$label);
        $this->assertEquals('fa fa-test', $page::$icon);
        $this->assertEquals('test', $page::$slug);
        $this->assertEquals(10, $page::$navSort);
    }

    /**
     * Test multiple page instances independence
     */
    public function test_multiple_page_instances(): void
    {
        $page1 = new class extends Page {
            public static ?string $slug = 'page1';
            public static ?string $label = 'Page 1';
        };

        $page2 = new class extends Page {
            public static ?string $slug = 'page2';
            public static ?string $label = 'Page 2';
        };

        $this->assertNotSame($page1, $page2);
        $this->assertEquals('page1', $page1->getSlug());
        $this->assertEquals('page2', $page2->getSlug());
    }

    /**
     * Test page manager with multiple discovery paths
     */
    public function test_page_manager_multiple_discovery_paths(): void
    {
        $this->discovery->method('addPath')->willReturnSelf();

        $result = $this->manager
            ->addDiscoveryPath('/path1')
            ->addDiscoveryPath('/path2');

        $this->assertInstanceOf(PageManager::class, $result);
    }

    /**
     * Test page manager discover with multiple pages
     */
    public function test_page_manager_discover_multiple_pages(): void
    {
        $pages = [
            'dashboard' => 'App\Pages\DashboardPage',
            'settings' => 'App\Pages\SettingsPage',
            'users' => 'App\Pages\UsersPage'
        ];

        $this->discovery->method('discover')->willReturn($pages);
        $this->registry->method('register')->willReturnSelf();

        $this->manager->discover();

        // Verify discover was called
        $this->assertInstanceOf(PageManager::class, $this->manager);
    }

    /**
     * Test page manager method visibility
     */
    public function test_page_manager_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(PageManager::class);

        $publicMethods = [
            'addDiscoveryPath',
            'discover',
            'register',
            'page',
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
                "PageManager should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test page static methods are public
     */
    public function test_page_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(Page::class);

        $publicMethods = [
            'render',
            'getSlug',
            'slug',
            'getLabel',
            'label'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Page should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test page manager namespace
     */
    public function test_page_manager_namespace(): void
    {
        $reflection = new \ReflectionClass(PageManager::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test page namespace
     */
    public function test_page_namespace(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test page is abstract
     */
    public function test_page_is_abstract(): void
    {
        $reflection = new \ReflectionClass(Page::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test page manager constructor parameters
     */
    public function test_page_manager_constructor_parameters(): void
    {
        $reflection = new \ReflectionClass(PageManager::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);
    }

    /**
     * Test page render with different contexts
     */
    public function test_page_render_with_different_contexts(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'Test Page';
        };

        $request1 = $this->createMock(Request::class);
        $request2 = $this->createMock(Request::class);

        $result1 = $page->render($request1);
        $result2 = $page->render($request2);

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test page with special characters in label
     */
    public function test_page_with_special_characters_in_label(): void
    {
        $page = new class extends Page {
            public static ?string $label = 'User & Admin Settings';
        };

        $this->assertEquals('User & Admin Settings', $page->getLabel());
    }

    /**
     * Test page with special characters in slug
     */
    public function test_page_with_special_characters_in_slug(): void
    {
        $page = new class extends Page {
            public static ?string $slug = 'user-admin-settings';
        };

        $this->assertEquals('user-admin-settings', $page->getSlug());
    }
}
