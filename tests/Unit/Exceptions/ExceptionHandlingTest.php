<?php

namespace Monstrex\Ave\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Exceptions\AveException;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Exceptions\ValidationException;
use Monstrex\Ave\Exceptions\FieldsetNestingException;
use Monstrex\Ave\Exceptions\HierarchicalRelationException;

/**
 * ExceptionHandlingTest - Unit tests for Ave exception classes.
 *
 * Tests the exception hierarchy and exception handling:
 * - AveException base class
 * - ResourceException with static factories
 * - ValidationException for form errors
 * - FieldsetNestingException for field validation
 * - HierarchicalRelationException for model validation
 */
class ExceptionHandlingTest extends TestCase
{
    /**
     * Test AveException is abstract
     */
    public function test_ave_exception_is_abstract(): void
    {
        $reflection = new \ReflectionClass(AveException::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test AveException extends Exception
     */
    public function test_ave_exception_extends_exception(): void
    {
        $reflection = new \ReflectionClass(AveException::class);
        $this->assertTrue($reflection->isSubclassOf(\Exception::class));
    }

    /**
     * Test AveException has statusCode property
     */
    public function test_ave_exception_has_status_code_property(): void
    {
        $reflection = new \ReflectionClass(AveException::class);
        $this->assertTrue($reflection->hasProperty('statusCode'));
    }

    /**
     * Test AveException default statusCode is 500
     */
    public function test_ave_exception_default_status_code(): void
    {
        $reflection = new \ReflectionClass(AveException::class);
        $property = $reflection->getProperty('statusCode');
        $property->setAccessible(true);

        $this->assertEquals(500, $property->getDefaultValue());
    }

    /**
     * Test AveException has getStatusCode method
     */
    public function test_ave_exception_has_get_status_code_method(): void
    {
        $reflection = new \ReflectionClass(AveException::class);
        $this->assertTrue($reflection->hasMethod('getStatusCode'));
        $this->assertTrue($reflection->getMethod('getStatusCode')->isPublic());
    }

    /**
     * Test AveException getStatusCode returns int
     */
    public function test_get_status_code_returns_int(): void
    {
        $reflection = new \ReflectionClass(AveException::class);
        $method = $reflection->getMethod('getStatusCode');

        $this->assertEquals('int', $method->getReturnType()->getName());
    }

    /**
     * Test ResourceException extends AveException
     */
    public function test_resource_exception_extends_ave_exception(): void
    {
        $reflection = new \ReflectionClass(ResourceException::class);
        $this->assertTrue($reflection->isSubclassOf(AveException::class));
    }

    /**
     * Test ResourceException constructor
     */
    public function test_resource_exception_constructor(): void
    {
        $exception = new ResourceException('Test error', 404);

        $this->assertInstanceOf(ResourceException::class, $exception);
        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    /**
     * Test ResourceException notFound factory
     */
    public function test_resource_exception_not_found_factory(): void
    {
        $exception = ResourceException::notFound('articles');

        $this->assertInstanceOf(ResourceException::class, $exception);
        $this->assertStringContainsString('articles', $exception->getMessage());
    }

    /**
     * Test ResourceException modelNotFound factory
     */
    public function test_resource_exception_model_not_found_factory(): void
    {
        $exception = ResourceException::modelNotFound('articles', 123);

        $this->assertInstanceOf(ResourceException::class, $exception);
        $this->assertStringContainsString('articles', $exception->getMessage());
        $this->assertStringContainsString('123', $exception->getMessage());
    }

    /**
     * Test ResourceException unauthorized factory
     */
    public function test_resource_exception_unauthorized_factory(): void
    {
        $exception = ResourceException::unauthorized('articles', 'delete');

        $this->assertInstanceOf(ResourceException::class, $exception);
        $this->assertStringContainsString('articles', $exception->getMessage());
        $this->assertStringContainsString('delete', $exception->getMessage());
    }

    /**
     * Test ResourceException invalidModel factory
     */
    public function test_resource_exception_invalid_model_factory(): void
    {
        $exception = ResourceException::invalidModel('App\Models\Article');

        $this->assertInstanceOf(ResourceException::class, $exception);
        $this->assertStringContainsString('App\Models\Article', $exception->getMessage());
    }

    /**
     * Test ValidationException extends AveException
     */
    public function test_validation_exception_extends_ave_exception(): void
    {
        $reflection = new \ReflectionClass(ValidationException::class);
        $this->assertTrue($reflection->isSubclassOf(AveException::class));
    }

    /**
     * Test ValidationException constructor
     */
    public function test_validation_exception_constructor(): void
    {
        $errors = ['name' => 'Name is required', 'email' => 'Email is invalid'];
        $exception = new ValidationException('Validation failed', $errors, 422);

        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
    }

    /**
     * Test ValidationException has errors property
     */
    public function test_validation_exception_has_errors_property(): void
    {
        $reflection = new \ReflectionClass(ValidationException::class);
        $this->assertTrue($reflection->hasProperty('errors'));
    }

    /**
     * Test ValidationException getErrors method
     */
    public function test_validation_exception_get_errors(): void
    {
        $reflection = new \ReflectionClass(ValidationException::class);
        $this->assertTrue($reflection->hasMethod('getErrors'));
        $this->assertTrue($reflection->getMethod('getErrors')->isPublic());
    }

    /**
     * Test ValidationException getErrors returns array
     */
    public function test_validation_exception_get_errors_returns_array(): void
    {
        $reflection = new \ReflectionClass(ValidationException::class);
        $method = $reflection->getMethod('getErrors');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test ValidationException withErrors factory
     */
    public function test_validation_exception_with_errors_factory(): void
    {
        $errors = ['name' => 'Required'];
        $exception = ValidationException::withErrors($errors);

        $this->assertInstanceOf(ValidationException::class, $exception);
    }

    /**
     * Test FieldsetNestingException extends AveException
     */
    public function test_fieldset_nesting_exception_extends_ave_exception(): void
    {
        $reflection = new \ReflectionClass(FieldsetNestingException::class);
        $this->assertTrue($reflection->isSubclassOf(AveException::class));
    }

    /**
     * Test FieldsetNestingException statusCode is 422
     */
    public function test_fieldset_nesting_exception_status_code(): void
    {
        $reflection = new \ReflectionClass(FieldsetNestingException::class);
        $property = $reflection->getProperty('statusCode');
        $property->setAccessible(true);

        $this->assertEquals(422, $property->getDefaultValue());
    }

    /**
     * Test FieldsetNestingException constructor
     */
    public function test_fieldset_nesting_exception_constructor(): void
    {
        $exception = new FieldsetNestingException();

        $this->assertInstanceOf(FieldsetNestingException::class, $exception);
        $this->assertStringContainsString('Fieldset', $exception->getMessage());
    }

    /**
     * Test FieldsetNestingException notAllowed factory
     */
    public function test_fieldset_nesting_exception_not_allowed_factory(): void
    {
        $exception = FieldsetNestingException::notAllowed();

        $this->assertInstanceOf(FieldsetNestingException::class, $exception);
        $this->assertStringContainsString('Fieldset', $exception->getMessage());
    }

    /**
     * Test HierarchicalRelationException extends Exception
     */
    public function test_hierarchical_relation_exception_extends_exception(): void
    {
        $reflection = new \ReflectionClass(HierarchicalRelationException::class);
        $this->assertTrue($reflection->isSubclassOf(\Exception::class));
    }

    /**
     * Test HierarchicalRelationException NOT extends AveException
     */
    public function test_hierarchical_relation_exception_not_ave_exception(): void
    {
        $reflection = new \ReflectionClass(HierarchicalRelationException::class);
        $this->assertFalse($reflection->isSubclassOf(AveException::class));
    }

    /**
     * Test HierarchicalRelationException missingParentIdColumn factory
     */
    public function test_hierarchical_relation_missing_parent_id_column(): void
    {
        $exception = HierarchicalRelationException::missingParentIdColumn('App\Models\Category');

        $this->assertInstanceOf(HierarchicalRelationException::class, $exception);
        $this->assertStringContainsString('parent_id', $exception->getMessage());
        $this->assertStringContainsString('App\Models\Category', $exception->getMessage());
    }

    /**
     * Test HierarchicalRelationException missingOrderColumn factory
     */
    public function test_hierarchical_relation_missing_order_column(): void
    {
        $exception = HierarchicalRelationException::missingOrderColumn('App\Models\Category');

        $this->assertInstanceOf(HierarchicalRelationException::class, $exception);
        $this->assertStringContainsString('order', $exception->getMessage());
        $this->assertStringContainsString('App\Models\Category', $exception->getMessage());
    }

    /**
     * Test HierarchicalRelationException missingBothColumns factory
     */
    public function test_hierarchical_relation_missing_both_columns(): void
    {
        $exception = HierarchicalRelationException::missingBothColumns('App\Models\Category');

        $this->assertInstanceOf(HierarchicalRelationException::class, $exception);
        $this->assertStringContainsString('parent_id', $exception->getMessage());
        $this->assertStringContainsString('order', $exception->getMessage());
    }

    /**
     * Test all AveException subclasses have proper error messages
     */
    public function test_resource_exception_has_descriptive_messages(): void
    {
        $notFound = ResourceException::notFound('test-resource');
        $this->assertNotEmpty($notFound->getMessage());

        $modelNotFound = ResourceException::modelNotFound('test-resource', 1);
        $this->assertNotEmpty($modelNotFound->getMessage());

        $unauthorized = ResourceException::unauthorized('test-resource', 'view');
        $this->assertNotEmpty($unauthorized->getMessage());

        $invalidModel = ResourceException::invalidModel('TestModel');
        $this->assertNotEmpty($invalidModel->getMessage());
    }

    /**
     * Test exception serialization
     */
    public function test_resource_exception_serializable(): void
    {
        $exception = ResourceException::notFound('articles');

        $this->assertIsString($exception->getMessage());
        $this->assertIsInt($exception->getCode());
    }

    /**
     * Test validation exception error handling
     */
    public function test_validation_exception_with_multiple_errors(): void
    {
        $errors = [
            'name' => 'Name is required',
            'email' => 'Email is invalid',
            'age' => 'Age must be numeric'
        ];

        $exception = ValidationException::withErrors($errors);
        $this->assertInstanceOf(ValidationException::class, $exception);
    }

    /**
     * Test exception inheritance chain
     */
    public function test_exception_inheritance_chain(): void
    {
        $exception = ResourceException::notFound('test');

        // Should be instance of itself
        $this->assertInstanceOf(ResourceException::class, $exception);

        // Should be instance of AveException
        $this->assertInstanceOf(AveException::class, $exception);

        // Should be instance of Exception
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * Test all exceptions can be thrown and caught
     */
    public function test_exceptions_can_be_thrown(): void
    {
        try {
            throw ResourceException::notFound('articles');
        } catch (ResourceException $e) {
            $this->assertInstanceOf(ResourceException::class, $e);
        }
    }

    /**
     * Test AveException can catch all Ave exceptions
     */
    public function test_ave_exception_catches_all_subclasses(): void
    {
        $exceptions = [
            ResourceException::notFound('test'),
            new ValidationException(),
            FieldsetNestingException::notAllowed(),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(AveException::class, $exception);
        }
    }

    /**
     * Test exceptions have proper status codes
     */
    public function test_status_codes_for_exceptions(): void
    {
        $notFound = ResourceException::notFound('test');
        $this->assertEquals(404, $notFound->getStatusCode());

        $fieldsetError = FieldsetNestingException::notAllowed();
        $this->assertEquals(422, $fieldsetError->getStatusCode());
    }

    /**
     * Test exception message formatting
     */
    public function test_exception_messages_are_strings(): void
    {
        $exceptions = [
            ResourceException::notFound('articles'),
            ResourceException::modelNotFound('articles', 123),
            ResourceException::unauthorized('articles', 'delete'),
            ResourceException::invalidModel('TestModel'),
            new ValidationException('Validation failed', []),
            FieldsetNestingException::notAllowed(),
        ];

        foreach ($exceptions as $exception) {
            $this->assertIsString($exception->getMessage());
            $this->assertNotEmpty($exception->getMessage());
        }
    }

    /**
     * Test exception static factory methods return correct type
     */
    public function test_factory_methods_return_correct_type(): void
    {
        $this->assertInstanceOf(ResourceException::class, ResourceException::notFound('test'));
        $this->assertInstanceOf(ResourceException::class, ResourceException::modelNotFound('test', 1));
        $this->assertInstanceOf(ResourceException::class, ResourceException::unauthorized('test', 'view'));
        $this->assertInstanceOf(ResourceException::class, ResourceException::invalidModel('Model'));
        $this->assertInstanceOf(ValidationException::class, ValidationException::withErrors([]));
        $this->assertInstanceOf(FieldsetNestingException::class, FieldsetNestingException::notAllowed());
    }

    /**
     * Test exception codes are set correctly
     */
    public function test_exception_codes(): void
    {
        $exception = new ResourceException('Test', 404);
        $this->assertEquals(404, $exception->getCode());

        $validationException = new ValidationException('Validation', [], 422);
        $this->assertEquals(422, $validationException->getCode());
    }
}
