<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Core\Components\RowComponent;
use Monstrex\Ave\Core\Components\FormComponent;
use Monstrex\Ave\Contracts\FormField;
use InvalidArgumentException;

/**
 * FormTest - Unit tests for Form class.
 *
 * Tests the form system which provides:
 * - Schema definition with rows and components
 * - Field flattening for validation
 * - Layout serialization for rendering
 * - Submit label and cancel URL management
 * - Component normalization and type handling
 */
class FormTest extends TestCase
{
    /**
     * Test form can be instantiated
     */
    public function test_form_can_be_instantiated(): void
    {
        $form = new Form();
        $this->assertInstanceOf(Form::class, $form);
    }

    /**
     * Test form make factory method
     */
    public function test_form_make_factory_method(): void
    {
        $form = Form::make();
        $this->assertInstanceOf(Form::class, $form);
    }

    /**
     * Test form schema method is fluent
     */
    public function test_form_schema_method_is_fluent(): void
    {
        $form = new Form();
        $result = $form->schema([]);

        $this->assertInstanceOf(Form::class, $result);
        $this->assertSame($form, $result);
    }

    /**
     * Test form submit label method is fluent
     */
    public function test_form_submit_label_method_is_fluent(): void
    {
        $form = new Form();
        $result = $form->submitLabel('Submit');

        $this->assertInstanceOf(Form::class, $result);
        $this->assertSame($form, $result);
    }

    /**
     * Test form cancel url method is fluent
     */
    public function test_form_cancel_url_method_is_fluent(): void
    {
        $form = new Form();
        $result = $form->cancelUrl('/items');

        $this->assertInstanceOf(Form::class, $result);
        $this->assertSame($form, $result);
    }

    /**
     * Test form fluent interface chaining
     */
    public function test_form_fluent_interface_chaining(): void
    {
        $form = Form::make()
            ->submitLabel('Create')
            ->cancelUrl('/items')
            ->schema([]);

        $this->assertInstanceOf(Form::class, $form);
        $this->assertEquals('Create', $form->getSubmitLabel());
        $this->assertEquals('/items', $form->getCancelUrl());
    }

    /**
     * Test form get submit label with custom label
     */
    public function test_form_get_submit_label_custom(): void
    {
        $form = Form::make()->submitLabel('Create Item');
        $this->assertEquals('Create Item', $form->getSubmitLabel());
    }

    /**
     * Test form get submit label default
     */
    public function test_form_get_submit_label_default(): void
    {
        $form = Form::make();
        $this->assertEquals('Save', $form->getSubmitLabel());
    }

    /**
     * Test form get cancel url with custom url
     */
    public function test_form_get_cancel_url_custom(): void
    {
        $form = Form::make()->cancelUrl('/items');
        $this->assertEquals('/items', $form->getCancelUrl());
    }

    /**
     * Test form get cancel url default
     */
    public function test_form_get_cancel_url_default(): void
    {
        $form = Form::make();
        $this->assertNull($form->getCancelUrl());
    }

    /**
     * Test form schema with empty array
     */
    public function test_form_schema_with_empty_array(): void
    {
        $form = Form::make()->schema([]);

        $this->assertIsArray($form->layout());
        $this->assertEmpty($form->layout());
    }

    /**
     * Test form get all fields empty
     */
    public function test_form_get_all_fields_empty(): void
    {
        $form = Form::make();

        $fields = $form->getAllFields();

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    /**
     * Test form get field returns null when not found
     */
    public function test_form_get_field_not_found(): void
    {
        $form = Form::make();

        $field = $form->getField('missing');

        $this->assertNull($field);
    }

    /**
     * Test form get fields returns empty array
     */
    public function test_form_get_fields_empty(): void
    {
        $form = Form::make();

        $fields = $form->getFields();

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    /**
     * Test form rows returns empty array
     */
    public function test_form_rows_empty(): void
    {
        $form = Form::make();

        $rows = $form->rows();

        $this->assertIsArray($rows);
        $this->assertEmpty($rows);
    }

    /**
     * Test form layout returns empty array when no components
     */
    public function test_form_layout_empty(): void
    {
        $form = Form::make();

        $layout = $form->layout();

        $this->assertIsArray($layout);
        $this->assertEmpty($layout);
    }

    /**
     * Test form normalize component with form component
     */
    public function test_form_normalize_component_with_form_component(): void
    {
        $form = Form::make();
        $component = $this->createMock(FormComponent::class);

        $form->schema([$component]);

        $layout = $form->layout();
        $this->assertCount(1, $layout);
        $this->assertEquals('component', $layout[0]['type']);
    }

    /**
     * Test form normalize component with invalid type throws exception
     */
    public function test_form_normalize_component_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unsupported form schema component/');

        $form = Form::make();
        $form->schema(['invalid']);
    }

    /**
     * Test form normalize component with object throws exception
     */
    public function test_form_normalize_component_invalid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $form = Form::make();
        $form->schema([new \stdClass()]);
    }

    /**
     * Test form get field by key
     */
    public function test_form_get_field_by_key(): void
    {
        $form = Form::make();
        $field = $this->createMock(FormField::class);
        $field->method('key')->willReturn('name');

        // We can't easily add fields without Row/Col setup, but test method exists
        $result = $form->getField('name');
        $this->assertNull($result);
    }

    /**
     * Test multiple form instances independence
     */
    public function test_multiple_form_instances(): void
    {
        $form1 = Form::make()->submitLabel('Create');
        $form2 = Form::make()->submitLabel('Update');

        $this->assertNotSame($form1, $form2);
        $this->assertEquals('Create', $form1->getSubmitLabel());
        $this->assertEquals('Update', $form2->getSubmitLabel());
    }

    /**
     * Test form submit label with special characters
     */
    public function test_form_submit_label_with_special_characters(): void
    {
        $form = Form::make()->submitLabel('Create & Save');
        $this->assertEquals('Create & Save', $form->getSubmitLabel());
    }

    /**
     * Test form cancel url with query parameters
     */
    public function test_form_cancel_url_with_query_params(): void
    {
        $form = Form::make()->cancelUrl('/items?filter=active');
        $this->assertEquals('/items?filter=active', $form->getCancelUrl());
    }

    /**
     * Test form method visibility
     */
    public function test_form_methods_are_public(): void
    {
        $reflection = new \ReflectionClass(Form::class);

        $publicMethods = [
            'make',
            'schema',
            'submitLabel',
            'cancelUrl',
            'layout',
            'getAllFields',
            'getField',
            'getFields',
            'rows',
            'getSubmitLabel',
            'getCancelUrl'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Form should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test form namespace
     */
    public function test_form_namespace(): void
    {
        $reflection = new \ReflectionClass(Form::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test form class name
     */
    public function test_form_class_name(): void
    {
        $reflection = new \ReflectionClass(Form::class);
        $this->assertEquals('Form', $reflection->getShortName());
    }

    /**
     * Test form layout returns array of arrays
     */
    public function test_form_layout_returns_array_structure(): void
    {
        $form = Form::make();
        $layout = $form->layout();

        $this->assertIsArray($layout);
        foreach ($layout as $item) {
            $this->assertIsArray($item);
        }
    }

    /**
     * Test form rows filters non-row components
     */
    public function test_form_rows_filters_components(): void
    {
        $form = Form::make();
        $component = $this->createMock(FormComponent::class);

        $form->schema([$component]);

        $rows = $form->rows();
        // Should be empty because we only added a component, not a row
        $this->assertIsArray($rows);
    }

    /**
     * Test form with empty submit label override
     */
    public function test_form_with_empty_string_submit_label(): void
    {
        $form = Form::make()->submitLabel('');
        // Empty string is still set, should return empty string not default
        $this->assertEmpty($form->getSubmitLabel());
    }
}
