<?php

namespace Tests\Unit\Core\Components;

use Monstrex\Ave\Core\Components\Div;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\FormContext;
use PHPUnit\Framework\TestCase;

class DivTest extends TestCase
{
    /**
     * Test creating a Div with CSS classes
     */
    public function test_can_create_div_with_classes()
    {
        $div = Div::make('row');

        $this->assertEquals('row', $div->getClasses());
    }

    /**
     * Test adding multiple classes to a Div
     */
    public function test_can_add_multiple_classes()
    {
        $div = Div::make('row gap-4 mb-5');

        $this->assertEquals('row gap-4 mb-5', $div->getClasses());
    }

    /**
     * Test Div with header
     */
    public function test_can_set_header()
    {
        $div = Div::make('panel')->header('Main Section');

        $this->assertEquals('Main Section', $div->getHeader());
    }

    /**
     * Test Div with footer
     */
    public function test_can_set_footer()
    {
        $div = Div::make('panel')->footer('End of section');

        $this->assertEquals('End of section', $div->getFooter());
    }

    /**
     * Test Div with both header and footer
     */
    public function test_can_set_header_and_footer()
    {
        $div = Div::make('panel')
            ->header('Top')
            ->footer('Bottom');

        $this->assertEquals('Top', $div->getHeader());
        $this->assertEquals('Bottom', $div->getFooter());
    }

    /**
     * Test Div with HTML attributes
     */
    public function test_can_set_attributes()
    {
        $div = Div::make('panel')->attributes([
            'id' => 'main-panel',
            'data-test' => 'value',
            'aria-label' => 'Main content',
        ]);

        $attrs = $div->getAttributes();
        $this->assertEquals('main-panel', $attrs['id']);
        $this->assertEquals('value', $attrs['data-test']);
        $this->assertEquals('Main content', $attrs['aria-label']);
    }

    /**
     * Test Div with direct fields (no nested containers)
     */
    public function test_can_add_fields_directly()
    {
        $div = Div::make('form-section')->schema([
            TextInput::make('name')->label('Name'),
            Textarea::make('description')->label('Description'),
        ]);

        $fields = $div->getFields();
        $this->assertCount(2, $fields);
        $this->assertEquals('name', $fields[0]->getKey());
        $this->assertEquals('description', $fields[1]->getKey());
    }

    /**
     * Test Div with nested Divs (containers)
     */
    public function test_can_add_nested_divs()
    {
        $div = Div::make('row')->schema([
            Div::make('col-6')->schema([
                TextInput::make('name'),
            ]),
            Div::make('col-6')->schema([
                TextInput::make('email'),
            ]),
        ]);

        $components = $div->getChildComponents();
        $this->assertCount(2, $components);
        $this->assertInstanceOf(Div::class, $components[0]);
        $this->assertInstanceOf(Div::class, $components[1]);
    }

    /**
     * Test Div with mixed content (fields + nested divs)
     */
    public function test_can_mix_fields_and_nested_divs()
    {
        $div = Div::make('card')->schema([
            TextInput::make('title'),
            Div::make('row')->schema([
                Div::make('col-6')->schema([
                    TextInput::make('name'),
                ]),
                Div::make('col-6')->schema([
                    TextInput::make('email'),
                ]),
            ]),
        ]);

        $fields = $div->getFields();
        $components = $div->getChildComponents();

        $this->assertCount(1, $fields);
        $this->assertEquals('title', $fields[0]->getKey());

        $this->assertCount(1, $components);
        $this->assertInstanceOf(Div::class, $components[0]);
    }

    /**
     * Test deeply nested Divs
     */
    public function test_can_create_deeply_nested_divs()
    {
        $div = Div::make('outer')->schema([
            Div::make('middle')->schema([
                Div::make('inner')->schema([
                    TextInput::make('deep_field'),
                ]),
            ]),
        ]);

        $outerComponents = $div->getChildComponents();
        $this->assertCount(1, $outerComponents);

        $middleDiv = $outerComponents[0];
        $this->assertInstanceOf(Div::class, $middleDiv);

        $innerComponents = $middleDiv->getChildComponents();
        $this->assertCount(1, $innerComponents);

        $innerDiv = $innerComponents[0];
        $this->assertInstanceOf(Div::class, $innerDiv);

        $innerFields = $innerDiv->getFields();
        $this->assertCount(1, $innerFields);
        $this->assertEquals('deep_field', $innerFields[0]->getKey());
    }

    /**
     * Test flattenFields() extracts all fields recursively
     */
    public function test_flatten_fields_extracts_all_nested_fields()
    {
        $div = Div::make('container')->schema([
            TextInput::make('field1'),
            Div::make('section')->schema([
                TextInput::make('field2'),
                Textarea::make('field3'),
                Div::make('subsection')->schema([
                    TextInput::make('field4'),
                ]),
            ]),
        ]);

        $allFields = $div->flattenFields();

        $this->assertCount(4, $allFields);
        $this->assertEquals('field1', $allFields[0]->getKey());
        $this->assertEquals('field2', $allFields[1]->getKey());
        $this->assertEquals('field3', $allFields[2]->getKey());
        $this->assertEquals('field4', $allFields[3]->getKey());
    }

    /**
     * Test hasChildComponents() returns true for mixed content
     */
    public function test_has_child_components_with_mixed_content()
    {
        $div = Div::make('panel')->schema([
            TextInput::make('field'),
            Div::make('nested')->schema([]),
        ]);

        $this->assertTrue($div->hasChildComponents());
    }

    /**
     * Test hasChildComponents() returns false for empty Div
     */
    public function test_has_child_components_empty_div()
    {
        $div = Div::make('empty');

        $this->assertFalse($div->hasChildComponents());
    }

    /**
     * Test that Div uses correct view template
     */
    public function test_div_uses_correct_view_template()
    {
        $div = Div::make('test-div');

        $this->assertEquals('ave::components.forms.div', $div->getViewTemplate());
    }

    /**
     * Test custom view override
     */
    public function test_can_override_view_template()
    {
        $div = Div::make('test-div')->view('custom.div.template');

        $this->assertEquals('custom.div.template', $div->getViewTemplate());
    }

    /**
     * Test schema() returns fluent interface
     */
    public function test_schema_returns_fluent_interface()
    {
        $div = Div::make('test');
        $result = $div->schema([]);

        $this->assertSame($div, $result);
    }

    /**
     * Test classes() returns fluent interface
     */
    public function test_classes_returns_fluent_interface()
    {
        $div = Div::make('initial');
        $result = $div->classes('new-classes');

        $this->assertSame($div, $result);
        $this->assertEquals('new-classes', $div->getClasses());
    }

    /**
     * Test method chaining
     */
    public function test_method_chaining()
    {
        $div = Div::make('panel')
            ->header('Title')
            ->footer('End')
            ->attributes(['id' => 'test'])
            ->schema([
                TextInput::make('name'),
            ]);

        $this->assertEquals('panel', $div->getClasses());
        $this->assertEquals('Title', $div->getHeader());
        $this->assertEquals('End', $div->getFooter());
        $this->assertEquals('test', $div->getAttributes()['id']);
        $this->assertCount(1, $div->getFields());
    }
}
