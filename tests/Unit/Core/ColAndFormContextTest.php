<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Core\FormContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Monstrex\Ave\Contracts\FormField;

/**
 * ColAndFormContextTest - Unit tests for Col and FormContext classes.
 *
 * Tests the layout and context management:
 * - Col: Bootstrap-like grid columns with field management
 * - FormContext: Form state management with data sources
 */
class ColAndFormContextTest extends TestCase
{
    /**
     * Test col can be instantiated
     */
    public function test_col_can_be_instantiated(): void
    {
        $col = new Col();
        $this->assertInstanceOf(Col::class, $col);
    }

    /**
     * Test col make factory default span
     */
    public function test_col_make_default_span(): void
    {
        $col = Col::make();
        $this->assertEquals(12, $col->getSpan());
    }

    /**
     * Test col make with custom span
     */
    public function test_col_make_with_span(): void
    {
        $col = Col::make(6);
        $this->assertEquals(6, $col->getSpan());
    }

    /**
     * Test col make clamps span between 1 and 12
     */
    public function test_col_span_clamping(): void
    {
        $colMin = Col::make(0);
        $this->assertEquals(1, $colMin->getSpan());

        $colMax = Col::make(20);
        $this->assertEquals(12, $colMax->getSpan());
    }

    /**
     * Test col fields method is fluent
     */
    public function test_col_fields_is_fluent(): void
    {
        $col = new Col();
        $result = $col->fields([]);

        $this->assertInstanceOf(Col::class, $result);
        $this->assertSame($col, $result);
    }

    /**
     * Test col add field is fluent
     */
    public function test_col_add_field_is_fluent(): void
    {
        $col = new Col();
        $field = $this->createMock(FormField::class);
        $result = $col->addField($field);

        $this->assertInstanceOf(Col::class, $result);
        $this->assertSame($col, $result);
    }

    /**
     * Test col add field
     */
    public function test_col_add_field(): void
    {
        $col = new Col();
        $field = $this->createMock(FormField::class);
        $col->addField($field);

        $this->assertCount(1, $col->getFields());
    }

    /**
     * Test col multiple fields
     */
    public function test_col_multiple_fields(): void
    {
        $col = Col::make(6);
        $field1 = $this->createMock(FormField::class);
        $field2 = $this->createMock(FormField::class);

        $col->addField($field1)->addField($field2);

        $this->assertCount(2, $col->getFields());
    }

    /**
     * Test col set fields
     */
    public function test_col_set_fields(): void
    {
        $col = new Col();
        $fields = [
            $this->createMock(FormField::class),
            $this->createMock(FormField::class)
        ];

        $col->fields($fields);

        $this->assertCount(2, $col->getFields());
    }

    /**
     * Test col to array
     */
    public function test_col_to_array(): void
    {
        $field = $this->createMock(FormField::class);
        $field->method('toArray')->willReturn(['key' => 'value']);

        $col = Col::make(6)->addField($field);
        $array = $col->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(6, $array['span']);
        $this->assertIsArray($array['fields']);
    }

    /**
     * Test col different span values
     */
    public function test_col_different_spans(): void
    {
        foreach ([1, 3, 6, 12] as $span) {
            $col = Col::make($span);
            $this->assertEquals($span, $col->getSpan());
        }
    }

    /**
     * Test form context create mode
     */
    public function test_form_context_create_mode(): void
    {
        $context = FormContext::forCreate();
        $this->assertTrue($context->isCreate());
        $this->assertFalse($context->isEdit());
    }

    /**
     * Test form context edit mode
     */
    public function test_form_context_edit_mode(): void
    {
        $model = $this->createMock(Model::class);
        $context = FormContext::forEdit($model);
        $this->assertTrue($context->isEdit());
        $this->assertFalse($context->isCreate());
    }

    /**
     * Test form context for data
     */
    public function test_form_context_for_data(): void
    {
        $data = [];
        $context = FormContext::forData($data);
        $this->assertTrue($context->isCreate());
        $this->assertNull($context->record());
    }

    /**
     * Test form context data source
     */
    public function test_form_context_data_source(): void
    {
        $context = FormContext::forCreate();
        $dataSource = $context->dataSource();

        $this->assertNull($dataSource);
    }

    /**
     * Test form context with old input
     */
    public function test_form_context_with_old_input(): void
    {
        $context = FormContext::forCreate();
        $oldInput = ['name' => 'John'];
        $result = $context->withOldInput($oldInput);

        $this->assertInstanceOf(FormContext::class, $result);
        $this->assertTrue($context->hasOldInput('name'));
        $this->assertEquals('John', $context->oldInput('name'));
    }

    /**
     * Test form context old input access
     */
    public function test_form_context_old_input_access(): void
    {
        $context = FormContext::forCreate()
            ->withOldInput(['email' => 'test@example.com']);

        $this->assertTrue($context->hasOldInput('email'));
        $this->assertEquals('test@example.com', $context->oldInput('email'));
    }

    /**
     * Test form context with errors
     */
    public function test_form_context_with_errors(): void
    {
        $context = FormContext::forCreate();
        $errors = new ViewErrorBag();
        $result = $context->withErrors($errors);

        $this->assertInstanceOf(FormContext::class, $result);
    }

    /**
     * Test form context errors
     */
    public function test_form_context_errors(): void
    {
        $context = FormContext::forCreate();
        $errors = $context->errors();

        $this->assertInstanceOf(ViewErrorBag::class, $errors);
    }

    /**
     * Test form context set record
     */
    public function test_form_context_set_record(): void
    {
        $context = FormContext::forCreate();
        $model = $this->createMock(Model::class);

        $context->setRecord($model);

        $this->assertNotNull($context->record());
    }

    /**
     * Test form context add error
     */
    public function test_form_context_add_error(): void
    {
        $context = FormContext::forCreate();
        $context->addError('email', 'Email is invalid');

        $this->assertTrue($context->hasError('email'));
        $errors = $context->getErrors('email');
        $this->assertContains('Email is invalid', $errors);
    }

    /**
     * Test form context has error
     */
    public function test_form_context_has_error(): void
    {
        $context = FormContext::forCreate();
        $context->addError('name', 'Name is required');

        $this->assertTrue($context->hasError('name'));
        $this->assertFalse($context->hasError('email'));
    }

    /**
     * Test form context get errors
     */
    public function test_form_context_get_errors(): void
    {
        $context = FormContext::forCreate();
        $context->addError('password', 'Password too short');
        $context->addError('password', 'Password must have number');

        $errors = $context->getErrors('password');
        $this->assertIsArray($errors);
        $this->assertCount(2, $errors);
    }

    /**
     * Test form context get meta
     */
    public function test_form_context_get_meta(): void
    {
        $context = FormContext::forCreate(['key' => 'value']);
        $this->assertEquals('value', $context->getMeta('key'));
    }

    /**
     * Test form context get meta with default
     */
    public function test_form_context_get_meta_default(): void
    {
        $context = FormContext::forCreate();
        $result = $context->getMeta('missing', 'default');

        $this->assertEquals('default', $result);
    }

    /**
     * Test form context register deferred action
     */
    public function test_form_context_register_deferred_action(): void
    {
        $context = FormContext::forCreate();
        $executed = false;

        $context->registerDeferredAction(function($record) use (&$executed) {
            $executed = true;
        });

        $model = $this->createMock(Model::class);
        $context->runDeferredActions($model);

        $this->assertTrue($executed);
    }

    /**
     * Test form context request
     */
    public function test_form_context_request(): void
    {
        $request = $this->createMock(Request::class);
        $context = FormContext::forCreate([], $request);

        $this->assertSame($request, $context->request());
    }

    /**
     * Test col method visibility
     */
    public function test_col_methods_public(): void
    {
        $reflection = new \ReflectionClass(Col::class);

        $publicMethods = [
            'make',
            'fields',
            'addField',
            'getFields',
            'getSpan',
            'toArray'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Col should have method: {$method}"
            );
        }
    }

    /**
     * Test form context method visibility
     */
    public function test_form_context_methods_public(): void
    {
        $reflection = new \ReflectionClass(FormContext::class);

        $publicMethods = [
            'forCreate',
            'forEdit',
            'forData',
            'withOldInput',
            'withErrors',
            'isCreate',
            'isEdit',
            'record',
            'setRecord',
            'hasOldInput',
            'oldInput',
            'hasError',
            'getErrors',
            'errors',
            'addError',
            'dataSource',
            'getMeta',
            'request'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "FormContext should have method: {$method}"
            );
        }
    }

    /**
     * Test col namespace
     */
    public function test_col_namespace(): void
    {
        $reflection = new \ReflectionClass(Col::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test form context namespace
     */
    public function test_form_context_namespace(): void
    {
        $reflection = new \ReflectionClass(FormContext::class);
        $this->assertEquals('Monstrex\\Ave\\Core', $reflection->getNamespaceName());
    }

    /**
     * Test form context constants
     */
    public function test_form_context_constants(): void
    {
        $this->assertEquals('create', FormContext::MODE_CREATE);
        $this->assertEquals('edit', FormContext::MODE_EDIT);
    }

    /**
     * Test col fluent field operations
     */
    public function test_col_fluent_field_operations(): void
    {
        $col = Col::make(6);
        $field1 = $this->createMock(FormField::class);
        $field2 = $this->createMock(FormField::class);

        $result = $col->addField($field1)->addField($field2);

        $this->assertInstanceOf(Col::class, $result);
        $this->assertCount(2, $col->getFields());
    }

    /**
     * Test form context factory methods return correct instances
     */
    public function test_form_context_factory_methods(): void
    {
        $create = FormContext::forCreate();
        $this->assertInstanceOf(FormContext::class, $create);

        $model = $this->createMock(Model::class);
        $edit = FormContext::forEdit($model);
        $this->assertInstanceOf(FormContext::class, $edit);

        $data = [];
        $forData = FormContext::forData($data);
        $this->assertInstanceOf(FormContext::class, $forData);
    }

    /**
     * Test multiple col instances independence
     */
    public function test_multiple_col_instances(): void
    {
        $col1 = Col::make(6);
        $col2 = Col::make(3);

        $this->assertEquals(6, $col1->getSpan());
        $this->assertEquals(3, $col2->getSpan());
        $this->assertNotSame($col1, $col2);
    }

    /**
     * Test multiple form context instances
     */
    public function test_multiple_form_context_instances(): void
    {
        $context1 = FormContext::forCreate();
        $context2 = FormContext::forCreate();

        $this->assertNotSame($context1, $context2);
        $this->assertTrue($context1->isCreate());
        $this->assertTrue($context2->isCreate());
    }
}
