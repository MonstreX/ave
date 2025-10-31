<?php

namespace Monstrex\Ave\Tests\Unit\Phase7;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Rendering\FieldRenderer;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\Toggle;

class FieldRendererTest extends TestCase
{
    protected FieldRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new FieldRenderer();
    }

    public function test_field_renderer_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FieldRenderer::class, $this->renderer);
    }

    public function test_prepare_field_data_text_input(): void
    {
        $field = TextInput::make('name')->label('Name')->required();

        // Using reflection to access protected method
        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('prepareFieldData');
        $method->setAccessible(true);

        $data = $method->invoke($this->renderer, $field, 'test value', []);

        $this->assertEquals('name', $data['name']);
        $this->assertEquals('Name', $data['label']);
        $this->assertEquals('test value', $data['value']);
        $this->assertTrue($data['required']);
        $this->assertFalse($data['hasError']);
    }

    public function test_prepare_field_data_with_errors(): void
    {
        $field = TextInput::make('email')->label('Email');

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('prepareFieldData');
        $method->setAccessible(true);

        $errors = ['Invalid email format'];
        $data = $method->invoke($this->renderer, $field, null, $errors);

        $this->assertTrue($data['hasError']);
        $this->assertEquals($errors, $data['errors']);
    }

    public function test_get_component_name_for_text_input(): void
    {
        $field = TextInput::make('name');

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getComponentName');
        $method->setAccessible(true);

        $componentName = $method->invoke($this->renderer, $field);
        $this->assertEquals('text-input', $componentName);
    }

    public function test_get_component_name_for_select(): void
    {
        $field = Select::make('status')->options(['active' => 'Active']);

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getComponentName');
        $method->setAccessible(true);

        $componentName = $method->invoke($this->renderer, $field);
        $this->assertEquals('select', $componentName);
    }

    public function test_get_component_name_for_toggle(): void
    {
        $field = Toggle::make('active');

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getComponentName');
        $method->setAccessible(true);

        $componentName = $method->invoke($this->renderer, $field);
        $this->assertEquals('toggle', $componentName);
    }
}
