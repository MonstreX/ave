<?php

namespace Monstrex\Ave\Tests\Unit\Core\Media;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Media\MediaRepository;

/**
 * MediaRepositoryTest - Unit tests for MediaRepository class.
 *
 * Tests the MediaRepository class which handles:
 * - Repository initialization
 * - Method availability and signatures
 * - Parameter validation
 */
class MediaRepositoryTest extends TestCase
{
    private MediaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create repository with media model class
        $this->repository = new MediaRepository('Monstrex\Ave\Models\Media');
    }

    /**
     * Test repository can be instantiated
     */
    public function test_repository_can_be_instantiated(): void
    {
        $this->assertInstanceOf(MediaRepository::class, $this->repository);
    }

    /**
     * Test repository initializes with default media model
     */
    public function test_repository_initializes_with_default_media_model(): void
    {
        $repository = new MediaRepository('Monstrex\Ave\Models\Media');

        $this->assertInstanceOf(MediaRepository::class, $repository);
    }

    /**
     * Test repository initializes with custom media model class
     */
    public function test_repository_initializes_with_custom_media_model_class(): void
    {
        $repository = new MediaRepository('App\Models\CustomMedia');

        $this->assertInstanceOf(MediaRepository::class, $repository);
    }

    /**
     * Test delete method exists and is callable
     */
    public function test_delete_method_is_callable(): void
    {
        // Just verify the method exists and can be accessed
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('delete'));
        $this->assertTrue($reflection->getMethod('delete')->isPublic());
    }

    /**
     * Test attach method exists and is callable
     */
    public function test_attach_method_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('attach'));
        $this->assertTrue($reflection->getMethod('attach')->isPublic());
    }

    /**
     * Test reorder method exists and is callable
     */
    public function test_reorder_method_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('reorder'));
        $this->assertTrue($reflection->getMethod('reorder')->isPublic());
    }

    /**
     * Test updateProps method exists and is callable
     */
    public function test_update_props_method_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('updateProps'));
        $this->assertTrue($reflection->getMethod('updateProps')->isPublic());
    }

    /**
     * Test count method exists and is callable
     */
    public function test_count_method_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('count'));
        $this->assertTrue($reflection->getMethod('count')->isPublic());
    }

    /**
     * Test allForCollection method exists and is callable
     */
    public function test_all_for_collection_method_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('allForCollection'));
        $this->assertTrue($reflection->getMethod('allForCollection')->isPublic());
    }

    /**
     * Test infoForIds method exists and is callable
     */
    public function test_info_for_ids_method_is_callable(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('infoForIds'));
        $this->assertTrue($reflection->getMethod('infoForIds')->isPublic());
    }

    /**
     * Test delete method has correct parameters
     */
    public function test_delete_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('delete');
        $parameters = $method->getParameters();

        $this->assertEquals(3, count($parameters));
        $this->assertEquals('model', $parameters[0]->getName());
        $this->assertEquals('collection', $parameters[1]->getName());
        $this->assertEquals('ids', $parameters[2]->getName());
    }

    /**
     * Test attach method has correct parameters
     */
    public function test_attach_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('attach');
        $parameters = $method->getParameters();

        $this->assertEquals(3, count($parameters));
        $this->assertEquals('model', $parameters[0]->getName());
        $this->assertEquals('collection', $parameters[1]->getName());
        $this->assertEquals('ids', $parameters[2]->getName());
    }

    /**
     * Test reorder method has correct parameters
     */
    public function test_reorder_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('reorder');
        $parameters = $method->getParameters();

        $this->assertEquals(3, count($parameters));
        $this->assertEquals('model', $parameters[0]->getName());
        $this->assertEquals('collection', $parameters[1]->getName());
        $this->assertEquals('orderedIds', $parameters[2]->getName());
    }

    /**
     * Test updateProps method has correct parameters
     */
    public function test_update_props_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('updateProps');
        $parameters = $method->getParameters();

        $this->assertEquals(3, count($parameters));
        $this->assertEquals('model', $parameters[0]->getName());
        $this->assertEquals('collection', $parameters[1]->getName());
        $this->assertEquals('props', $parameters[2]->getName());
    }

    /**
     * Test count method has correct parameters
     */
    public function test_count_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('count');
        $parameters = $method->getParameters();

        $this->assertEquals(2, count($parameters));
        $this->assertEquals('model', $parameters[0]->getName());
        $this->assertEquals('collection', $parameters[1]->getName());
    }

    /**
     * Test allForCollection method has correct parameters
     */
    public function test_all_for_collection_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('allForCollection');
        $parameters = $method->getParameters();

        $this->assertEquals(2, count($parameters));
        $this->assertEquals('model', $parameters[0]->getName());
        $this->assertEquals('collection', $parameters[1]->getName());
    }

    /**
     * Test infoForIds method has correct parameters
     */
    public function test_info_for_ids_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('infoForIds');
        $parameters = $method->getParameters();

        $this->assertEquals(1, count($parameters));
        $this->assertEquals('ids', $parameters[0]->getName());
    }

    /**
     * Test delete method returns void
     */
    public function test_delete_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('delete');

        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    /**
     * Test attach method returns void
     */
    public function test_attach_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('attach');

        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    /**
     * Test reorder method returns void
     */
    public function test_reorder_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('reorder');

        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    /**
     * Test updateProps method returns void
     */
    public function test_update_props_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('updateProps');

        $this->assertEquals('void', $method->getReturnType()->getName());
    }

    /**
     * Test count method returns int
     */
    public function test_count_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('count');

        $this->assertEquals('int', $method->getReturnType()->getName());
    }

    /**
     * Test allForCollection method returns array
     */
    public function test_all_for_collection_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('allForCollection');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test infoForIds method returns array
     */
    public function test_info_for_ids_method_return_type(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('infoForIds');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test repository constructor accepts string parameter
     */
    public function test_constructor_accepts_string_parameter(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertEquals(1, count($parameters));
        $this->assertEquals('mediaModelClass', $parameters[0]->getName());
    }

    /**
     * Test repository has correct namespace
     */
    public function test_repository_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass($this->repository);

        $this->assertEquals('Monstrex\Ave\Core\Media', $reflection->getNamespaceName());
    }

    /**
     * Test repository class name
     */
    public function test_repository_class_name(): void
    {
        $reflection = new \ReflectionClass($this->repository);

        $this->assertEquals('MediaRepository', $reflection->getShortName());
    }

    /**
     * Test multiple repository instances can be created
     */
    public function test_multiple_repository_instances(): void
    {
        $repo1 = new MediaRepository('Monstrex\Ave\Models\Media');
        $repo2 = new MediaRepository('App\Models\CustomMedia');

        $this->assertInstanceOf(MediaRepository::class, $repo1);
        $this->assertInstanceOf(MediaRepository::class, $repo2);
        $this->assertNotSame($repo1, $repo2);
    }

    /**
     * Test repository methods are public
     */
    public function test_all_methods_are_public(): void
    {
        $reflection = new \ReflectionClass($this->repository);
        $methods = ['delete', 'attach', 'reorder', 'updateProps', 'count', 'allForCollection', 'infoForIds'];

        foreach ($methods as $methodName) {
            $this->assertTrue(
                $reflection->getMethod($methodName)->isPublic(),
                "Method {$methodName} should be public"
            );
        }
    }
}
