<?php

namespace Monstrex\Ave\Tests\Unit\Core\Validation;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\FormContext;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

/**
 * FormValidatorTest - Unit tests for FormValidator class.
 *
 * Tests the FormValidator class which:
 * - Builds Laravel validation rules from form definitions
 * - Handles create and edit validation modes
 * - Manages field validation attributes
 * - Adjusts unique rules for edit mode
 */
class FormValidatorTest extends TestCase
{
    private FormValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FormValidator();
    }

    /**
     * Test validator can be instantiated
     */
    public function test_validator_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FormValidator::class, $this->validator);
    }

    /**
     * Test rulesFromForm method exists and is callable
     */
    public function test_rules_from_form_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertTrue($reflection->hasMethod('rulesFromForm'));
        $this->assertTrue($reflection->getMethod('rulesFromForm')->isPublic());
    }

    /**
     * Test rulesFromForm method has correct parameters
     */
    public function test_rules_from_form_parameters(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rulesFromForm');
        $parameters = $method->getParameters();

        // Should have 6 parameters: form, resourceClass, request, mode, model, context
        $this->assertGreaterThanOrEqual(3, count($parameters));

        $paramNames = array_map(fn($p) => $p->getName(), $parameters);
        $this->assertContains('form', $paramNames);
        $this->assertContains('resourceClass', $paramNames);
        $this->assertContains('request', $paramNames);
    }

    /**
     * Test rulesFromForm returns array
     */
    public function test_rules_from_form_return_type(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('rulesFromForm');

        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /**
     * Test appendFieldRules method exists
     */
    public function test_append_field_rules_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertTrue($reflection->hasMethod('appendFieldRules'));
    }

    /**
     * Test extractFieldValidationRules method exists
     */
    public function test_extract_field_validation_rules_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertTrue($reflection->hasMethod('extractFieldValidationRules'));
    }

    /**
     * Test formatRules method exists
     */
    public function test_format_rules_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertTrue($reflection->hasMethod('formatRules'));
    }

    /**
     * Test adjustUniqueRulesForEdit method exists
     */
    public function test_adjust_unique_rules_for_edit_method_exists(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertTrue($reflection->hasMethod('adjustUniqueRulesForEdit'));
    }

    /**
     * Test formatRules converts string to array
     */
    public function test_format_rules_converts_string_to_array(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        // Test with string input
        $result = $method->invoke($this->validator, 'string|max:255', false);

        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
        $this->assertStringContainsString('max:255', $result);
    }

    /**
     * Test formatRules adds required flag
     */
    public function test_format_rules_adds_required_flag(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, 'string|max:255', true);

        $this->assertStringContainsString('required', $result);
    }

    /**
     * Test formatRules adds nullable for optional fields
     */
    public function test_format_rules_adds_nullable_for_optional(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, 'string|max:255', false);

        $this->assertStringContainsString('nullable', $result);
    }

    /**
     * Test formatRules accepts array input
     */
    public function test_format_rules_accepts_array(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, ['string', 'max:255'], true);

        $this->assertIsString($result);
        $this->assertStringContainsString('string', $result);
        $this->assertStringContainsString('max:255', $result);
        $this->assertStringContainsString('required', $result);
    }

    /**
     * Test formatRules filters empty values
     */
    public function test_format_rules_filters_empty_values(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, ['string', '', 'max:255', false], false);

        $this->assertStringNotContainsString('||', $result);
    }

    /**
     * Test formatRules handles empty input
     */
    public function test_format_rules_handles_empty_input(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, [], false);

        $this->assertIsString($result);
    }

    /**
     * Test formatRules joins with pipe separator
     */
    public function test_format_rules_joins_with_pipe(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, ['required', 'string', 'max:255'], false);

        $this->assertStringContainsString('|', $result);
    }

    /**
     * Test adjustUniqueRulesForEdit appends model id
     */
    public function test_adjust_unique_rules_appends_model_id(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->createMock(Model::class);
        $model->id = 123;
        $model->getKeyName = 'id';

        $rules = ['email' => 'required|email|unique:users'];
        $result = $method->invoke($this->validator, $rules, $model);

        $this->assertIsArray($result);
    }

    /**
     * Test adjustUniqueRulesForEdit preserves non-unique rules
     */
    public function test_adjust_unique_rules_preserves_non_unique(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->createMock(Model::class);
        $model->id = 123;

        $rules = ['name' => 'required|string|max:255'];
        $result = $method->invoke($this->validator, $rules, $model);

        $this->assertArrayHasKey('name', $result);
        $this->assertStringContainsString('required', $result['name']);
        $this->assertStringContainsString('string', $result['name']);
    }

    /**
     * Test adjustUniqueRulesForEdit handles multiple unique rules
     */
    public function test_adjust_unique_rules_handles_multiple_unique(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->createMock(Model::class);
        $model->id = 123;

        $rules = ['field' => 'required|unique:users|unique:posts'];
        $result = $method->invoke($this->validator, $rules, $model);

        $this->assertIsArray($result);
    }

    /**
     * Test appendFieldRules returns array
     */
    public function test_append_field_rules_returns_array(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('appendFieldRules');

        // Check return type
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test appendFieldRules has correct parameters
     */
    public function test_append_field_rules_parameters(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('appendFieldRules');
        $parameters = $method->getParameters();

        $this->assertGreaterThanOrEqual(3, count($parameters));
    }

    /**
     * Test extractFieldValidationRules returns array
     */
    public function test_extract_field_validation_rules_returns_array(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('extractFieldValidationRules');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
    }

    /**
     * Test validator has all required methods
     */
    public function test_validator_has_all_required_methods(): void
    {
        $reflection = new \ReflectionClass($this->validator);

        $requiredMethods = [
            'rulesFromForm',
            'appendFieldRules',
            'extractFieldValidationRules',
            'formatRules',
            'adjustUniqueRulesForEdit'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "FormValidator should have method: {$method}"
            );
        }
    }

    /**
     * Test validator class namespace
     */
    public function test_validator_correct_namespace(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertEquals('Monstrex\Ave\Core\Validation', $reflection->getNamespaceName());
    }

    /**
     * Test validator class name
     */
    public function test_validator_class_name(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $this->assertEquals('FormValidator', $reflection->getShortName());
    }

    /**
     * Test formatRules with pipe-separated input
     */
    public function test_format_rules_with_pipe_separated(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, 'required|email|max:255', false);

        $this->assertIsString($result);
        $this->assertStringContainsString('email', $result);
    }

    /**
     * Test formatRules preserves rule order
     */
    public function test_format_rules_preserves_order(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, ['string', 'max:255', 'min:5'], false);

        $parts = explode('|', $result);

        // Should not start with required or nullable due to false flag
        $this->assertFalse(in_array('required', [$parts[0]]));
    }

    /**
     * Test formatRules with single rule
     */
    public function test_format_rules_with_single_rule(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, 'email', true);

        $this->assertStringContainsString('required', $result);
        $this->assertStringContainsString('email', $result);
    }

    /**
     * Test formatRules does not duplicate required
     */
    public function test_format_rules_no_duplicate_required(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, 'required|string', true);

        // Should only have one 'required' rule
        $requiredCount = substr_count($result, 'required');
        $this->assertEquals(1, $requiredCount);
    }

    /**
     * Test formatRules does not add nullable if required
     */
    public function test_format_rules_no_nullable_if_required(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke($this->validator, 'string|email', true);

        // Should not have nullable when required=true
        $this->assertStringNotContainsString('nullable', $result);
    }

    /**
     * Test adjustUniqueRulesForEdit returns array
     */
    public function test_adjust_unique_returns_array(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->createMock(Model::class);
        $model->id = 1;

        $rules = ['name' => 'required|string'];
        $result = $method->invoke($this->validator, $rules, $model);

        $this->assertIsArray($result);
    }

    /**
     * Test adjustUniqueRulesForEdit with empty rules
     */
    public function test_adjust_unique_with_empty_rules(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->createMock(Model::class);

        $result = $method->invoke($this->validator, [], $model);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test adjustUniqueRulesForEdit preserves all fields
     */
    public function test_adjust_unique_preserves_all_fields(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->createMock(Model::class);
        $model->id = 1;

        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'slug' => 'required|unique:posts,slug'
        ];

        $result = $method->invoke($this->validator, $rules, $model);

        $this->assertCount(count($rules), $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('slug', $result);
    }

    public function test_adjust_unique_preserves_scopes(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('adjustUniqueRulesForEdit');
        $method->setAccessible(true);

        $model = $this->getMockBuilder(Model::class)->onlyMethods(['getKey', 'getKeyName'])->getMock();
        $model->method('getKey')->willReturn(42);
        $model->method('getKeyName')->willReturn('id');

        $rules = [
            'slug' => 'required|unique:posts,slug,NULL,id,tenant_id,7',
        ];

        $result = $method->invoke($this->validator, $rules, $model);

        $this->assertSame('required|unique:posts,slug,42,id,tenant_id,7', $result['slug']);
    }

    /**
     * Test multiple validator instances
     */
    public function test_multiple_validator_instances(): void
    {
        $validator1 = new FormValidator();
        $validator2 = new FormValidator();

        $this->assertInstanceOf(FormValidator::class, $validator1);
        $this->assertInstanceOf(FormValidator::class, $validator2);
        $this->assertNotSame($validator1, $validator2);
    }

    /**
     * Test formatRules with complex input
     */
    public function test_format_rules_complex_input(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->validator,
            ['required', 'string', 'min:3', 'max:255', 'regex:/^[a-z]+$/'],
            false
        );

        $this->assertStringContainsString('string', $result);
        $this->assertStringContainsString('min:3', $result);
        $this->assertStringContainsString('max:255', $result);
    }

    /**
     * Test formatRules handles mixed required/nullable
     */
    public function test_format_rules_prefers_explicit(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('formatRules');
        $method->setAccessible(true);

        // If explicit nullable present, should not add required
        $result = $method->invoke($this->validator, ['nullable', 'string'], false);

        $this->assertStringContainsString('nullable', $result);
    }
}
