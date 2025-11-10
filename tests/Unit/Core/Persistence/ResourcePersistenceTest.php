<?php

namespace Monstrex\Ave\Tests\Unit\Core\Persistence;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Contracts\Persistable;

/**
 * ResourcePersistenceTest - Unit tests for ResourcePersistence class.
 *
 * Tests the ResourcePersistence class which handles:
 * - Model creation operations
 * - Model update operations
 * - Model deletion operations
 * - Field persistence and data transformation
 * - Relation synchronization
 * - Event dispatching and transactional safety
 */
class ResourcePersistenceTest extends TestCase
{
    private ResourcePersistence $persistence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persistence = new ResourcePersistence();
    }

    /**
     * Test persistence can be instantiated
     */
    public function test_persistence_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ResourcePersistence::class, $this->persistence);
    }

    /**
     * Test ResourcePersistence implements Persistable interface
     */
    public function test_resource_persistence_implements_persistable(): void
    {
        $this->assertInstanceOf(Persistable::class, $this->persistence);
    }

    /**
     * Test create method exists and is public
     */
    public function test_create_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertTrue($reflection->hasMethod('create'));
        $this->assertTrue($reflection->getMethod('create')->isPublic());
    }

    /**
     * Test update method exists and is public
     */
    public function test_update_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertTrue($reflection->hasMethod('update'));
        $this->assertTrue($reflection->getMethod('update')->isPublic());
    }

    /**
     * Test delete method exists and is public
     */
    public function test_delete_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertTrue($reflection->hasMethod('delete'));
        $this->assertTrue($reflection->getMethod('delete')->isPublic());
    }

    /**
     * Test mergeFormData method exists
     */
    public function test_merge_form_data_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertTrue($reflection->hasMethod('mergeFormData'));
    }

    /**
     * Test syncRelations method exists
     */
    public function test_sync_relations_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertTrue($reflection->hasMethod('syncRelations'));
    }

    /**
     * Test create method signature
     */
    public function test_create_method_parameters(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('resourceClass', $paramNames);
        $this->assertContains('form', $paramNames);
        $this->assertContains('data', $paramNames);
        $this->assertContains('request', $paramNames);
        $this->assertContains('context', $paramNames);
    }

    /**
     * Test update method signature
     */
    public function test_update_method_parameters(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $parameters = $method->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('resourceClass', $paramNames);
        $this->assertContains('form', $paramNames);
        $this->assertContains('model', $paramNames);
        $this->assertContains('data', $paramNames);
        $this->assertContains('request', $paramNames);
        $this->assertContains('context', $paramNames);
    }

    /**
     * Test delete method signature
     */
    public function test_delete_method_parameters(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('delete');
        $parameters = $method->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('resourceClass', $paramNames);
        $this->assertContains('model', $paramNames);
    }

    /**
     * Test create method return type is Model
     */
    public function test_create_return_type(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        // Return type should be Model
        $this->assertTrue(
            strpos($returnType->getName(), 'Model') !== false ||
            $returnType->getName() === 'Illuminate\Database\Eloquent\Model'
        );
    }

    /**
     * Test update method return type is Model
     */
    public function test_update_return_type(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        // Return type should be Model
        $this->assertTrue(
            strpos($returnType->getName(), 'Model') !== false ||
            $returnType->getName() === 'Illuminate\Database\Eloquent\Model'
        );
    }

    /**
     * Test delete method return type is void
     */
    public function test_delete_return_type(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('delete');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    /**
     * Test mergeFormData return type is array
     */
    public function test_merge_form_data_return_type(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('mergeFormData');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * Test syncRelations return type is void
     */
    public function test_sync_relations_return_type(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('syncRelations');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    /**
     * Test mergeFormData parameters
     */
    public function test_merge_form_data_parameters(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('mergeFormData');
        $parameters = $method->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('form', $paramNames);
        $this->assertContains('model', $paramNames);
        $this->assertContains('data', $paramNames);
        $this->assertContains('request', $paramNames);
        $this->assertContains('context', $paramNames);
    }

    /**
     * Test syncRelations parameters
     */
    public function test_sync_relations_parameters(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('syncRelations');
        $parameters = $method->getParameters();

        $paramNames = array_map(fn($p) => $p->getName(), $parameters);

        $this->assertContains('resourceClass', $paramNames);
        $this->assertContains('model', $paramNames);
        $this->assertContains('data', $paramNames);
        $this->assertContains('request', $paramNames);
    }

    /**
     * Test class namespace
     */
    public function test_class_namespace(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertEquals('Monstrex\Ave\Core\Persistence', $reflection->getNamespaceName());
    }

    /**
     * Test class name
     */
    public function test_class_name(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $this->assertEquals('ResourcePersistence', $reflection->getShortName());
    }

    /**
     * Test all required methods are public
     */
    public function test_all_required_methods_are_public(): void
    {
        $reflection = new \ReflectionClass($this->persistence);

        $publicMethods = ['create', 'update', 'delete'];

        foreach ($publicMethods as $methodName) {
            $this->assertTrue(
                $reflection->getMethod($methodName)->isPublic(),
                "Method {$methodName} should be public"
            );
        }
    }

    /**
     * Test multiple persistence instances
     */
    public function test_multiple_persistence_instances(): void
    {
        $persistence1 = new ResourcePersistence();
        $persistence2 = new ResourcePersistence();

        $this->assertInstanceOf(ResourcePersistence::class, $persistence1);
        $this->assertInstanceOf(ResourcePersistence::class, $persistence2);
        $this->assertNotSame($persistence1, $persistence2);
    }

    /**
     * Test create parameter types
     */
    public function test_create_resourceClass_parameter_is_string(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();

        $resourceClassParam = array_filter($parameters, fn($p) => $p->getName() === 'resourceClass');
        $resourceClassParam = reset($resourceClassParam);

        $this->assertTrue($resourceClassParam->hasType());
        $this->assertEquals('string', $resourceClassParam->getType()->getName());
    }

    /**
     * Test update model parameter
     */
    public function test_update_model_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $parameters = $method->getParameters();

        $modelParam = array_filter($parameters, fn($p) => $p->getName() === 'model');
        $this->assertNotEmpty($modelParam);
    }

    /**
     * Test delete model parameter
     */
    public function test_delete_model_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('delete');
        $parameters = $method->getParameters();

        $modelParam = array_filter($parameters, fn($p) => $p->getName() === 'model');
        $this->assertNotEmpty($modelParam);
    }

    /**
     * Test create form parameter
     */
    public function test_create_form_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();

        $formParam = array_filter($parameters, fn($p) => $p->getName() === 'form');
        $this->assertNotEmpty($formParam);
    }

    /**
     * Test update form parameter
     */
    public function test_update_form_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $parameters = $method->getParameters();

        $formParam = array_filter($parameters, fn($p) => $p->getName() === 'form');
        $this->assertNotEmpty($formParam);
    }

    /**
     * Test create data parameter is array
     */
    public function test_create_data_parameter_is_array(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();

        $dataParam = array_filter($parameters, fn($p) => $p->getName() === 'data');
        $dataParam = reset($dataParam);

        $this->assertTrue($dataParam->hasType());
        $this->assertEquals('array', $dataParam->getType()->getName());
    }

    /**
     * Test update data parameter is array
     */
    public function test_update_data_parameter_is_array(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $parameters = $method->getParameters();

        $dataParam = array_filter($parameters, fn($p) => $p->getName() === 'data');
        $dataParam = reset($dataParam);

        $this->assertTrue($dataParam->hasType());
        $this->assertEquals('array', $dataParam->getType()->getName());
    }

    /**
     * Test mergeFormData data parameter is array
     */
    public function test_merge_form_data_data_parameter_is_array(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('mergeFormData');
        $parameters = $method->getParameters();

        $dataParam = array_filter($parameters, fn($p) => $p->getName() === 'data');
        $dataParam = reset($dataParam);

        $this->assertTrue($dataParam->hasType());
        $this->assertEquals('array', $dataParam->getType()->getName());
    }

    /**
     * Test persistence interface contract
     */
    public function test_persistence_implements_contract(): void
    {
        $reflection = new \ReflectionClass($this->persistence);

        // Should implement Persistable interface
        $interfaces = $reflection->getInterfaceNames();
        $this->assertContains('Monstrex\Ave\Contracts\Persistable', $interfaces);
    }

    /**
     * Test class has no abstract methods (fully implemented)
     */
    public function test_class_is_fully_implemented(): void
    {
        $reflection = new \ReflectionClass($this->persistence);

        // Should not be abstract since it implements the interface
        $this->assertFalse($reflection->isAbstract());
    }

    /**
     * Test mergeFormData is protected or private
     */
    public function test_merge_form_data_is_protected(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('mergeFormData');

        $this->assertTrue($method->isProtected() || $method->isPrivate());
    }

    /**
     * Test syncRelations is protected or private
     */
    public function test_sync_relations_is_protected(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('syncRelations');

        $this->assertTrue($method->isProtected() || $method->isPrivate());
    }

    /**
     * Test create request parameter
     */
    public function test_create_request_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();

        $requestParam = array_filter($parameters, fn($p) => $p->getName() === 'request');
        $this->assertNotEmpty($requestParam);
    }

    /**
     * Test update request parameter
     */
    public function test_update_request_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $parameters = $method->getParameters();

        $requestParam = array_filter($parameters, fn($p) => $p->getName() === 'request');
        $this->assertNotEmpty($requestParam);
    }

    /**
     * Test create context parameter
     */
    public function test_create_context_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('create');
        $parameters = $method->getParameters();

        $contextParam = array_filter($parameters, fn($p) => $p->getName() === 'context');
        $this->assertNotEmpty($contextParam);
    }

    /**
     * Test update context parameter
     */
    public function test_update_context_parameter_exists(): void
    {
        $reflection = new \ReflectionClass($this->persistence);
        $method = $reflection->getMethod('update');
        $parameters = $method->getParameters();

        $contextParam = array_filter($parameters, fn($p) => $p->getName() === 'context');
        $this->assertNotEmpty($contextParam);
    }
}
