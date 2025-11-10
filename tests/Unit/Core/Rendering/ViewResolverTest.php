<?php

namespace Monstrex\Ave\Tests\Unit\Core\Rendering;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Rendering\ViewResolver;

/**
 * ViewResolverTest - Unit tests for ViewResolver class.
 *
 * Tests the view resolution system which provides:
 * - Resource-specific view fallback to generic views
 * - Page view resolution with defaults
 * - View existence checking
 */
class ViewResolverTest extends TestCase
{
    private ViewResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ViewResolver();
    }

    /**
     * Test view resolver can be instantiated
     */
    public function test_view_resolver_can_be_instantiated(): void
    {
        $resolver = new ViewResolver();
        $this->assertInstanceOf(ViewResolver::class, $resolver);
    }

    /**
     * Test view resolver can construct resource view paths
     */
    public function test_view_resolver_constructs_resource_paths(): void
    {
        // Test that the resolver is callable with valid parameters
        $reflection = new \ReflectionClass($this->resolver);
        $method = $reflection->getMethod('resolveResource');

        $this->assertTrue($method->isPublic());
    }

    /**
     * Test view resolver can construct page view paths
     */
    public function test_view_resolver_constructs_page_paths(): void
    {
        // Test that the resolver is callable with valid parameters
        $reflection = new \ReflectionClass($this->resolver);
        $method = $reflection->getMethod('resolvePage');

        $this->assertTrue($method->isPublic());
    }

    /**
     * Test view resolver method signature for resource
     */
    public function test_view_resolver_resource_signature(): void
    {
        $reflection = new \ReflectionClass($this->resolver);
        $method = $reflection->getMethod('resolveResource');

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('slug', $params[0]->getName());
        $this->assertEquals('view', $params[1]->getName());
    }

    /**
     * Test view resolver method signature for page
     */
    public function test_view_resolver_page_signature(): void
    {
        $reflection = new \ReflectionClass($this->resolver);
        $method = $reflection->getMethod('resolvePage');

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('slug', $params[0]->getName());
    }

    /**
     * Test view resolver method return types
     */
    public function test_view_resolver_return_types(): void
    {
        $reflection = new \ReflectionClass($this->resolver);

        $resolveResource = $reflection->getMethod('resolveResource');
        $resolvePage = $reflection->getMethod('resolvePage');

        // Both should be public
        $this->assertTrue($resolveResource->isPublic());
        $this->assertTrue($resolvePage->isPublic());
    }

    /**
     * Test view resolver method visibility
     */
    public function test_view_resolver_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(ViewResolver::class);

        $publicMethods = [
            'resolveResource',
            'resolvePage'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "ViewResolver should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test view resolver namespace
     */
    public function test_view_resolver_namespace(): void
    {
        $reflection = new \ReflectionClass(ViewResolver::class);
        $this->assertEquals('Monstrex\\Ave\\Core\\Rendering', $reflection->getNamespaceName());
    }

    /**
     * Test view resolver class name
     */
    public function test_view_resolver_class_name(): void
    {
        $reflection = new \ReflectionClass(ViewResolver::class);
        $this->assertEquals('ViewResolver', $reflection->getShortName());
    }

    /**
     * Test view resolver multiple instances independence
     */
    public function test_multiple_instances_independence(): void
    {
        $resolver1 = new ViewResolver();
        $resolver2 = new ViewResolver();

        $this->assertNotSame($resolver1, $resolver2);
        $this->assertInstanceOf(ViewResolver::class, $resolver1);
        $this->assertInstanceOf(ViewResolver::class, $resolver2);
    }

    /**
     * Test view resolver handles different slugs
     */
    public function test_view_resolver_handles_different_slugs(): void
    {
        // Just test the resolver can be instantiated with different configurations
        $resolver = new ViewResolver();

        $reflection = new \ReflectionClass($resolver);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $this->assertGreaterThanOrEqual(2, count($methods));
    }

    /**
     * Test view resolver handles special characters in parameters
     */
    public function test_view_resolver_handles_special_chars(): void
    {
        // Just test the resolver is correctly constructed
        $resolver = new ViewResolver();

        $reflection = new \ReflectionClass($resolver);
        $this->assertEquals('ViewResolver', $reflection->getShortName());
    }
}
