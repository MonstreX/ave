<?php

namespace Monstrex\Ave\Tests\Unit\Phase7;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Rendering\FormRenderer;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormRow;
use Monstrex\Ave\Core\FormColumn;
use Monstrex\Ave\Core\Fields\TextInput;

class FormRendererTest extends TestCase
{
    protected FormRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new FormRenderer();
    }

    public function test_form_renderer_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FormRenderer::class, $this->renderer);
    }

    public function test_form_renderer_has_field_renderer(): void
    {
        $reflection = new \ReflectionClass($this->renderer);
        $property = $reflection->getProperty('fieldRenderer');
        $property->setAccessible(true);

        $fieldRenderer = $property->getValue($this->renderer);
        $this->assertNotNull($fieldRenderer);
    }

    public function test_get_field_value_from_null_model(): void
    {
        $field = TextInput::make('name');

        $reflection = new \ReflectionClass($this->renderer);
        $method = $reflection->getMethod('getFieldValue');
        $method->setAccessible(true);

        $value = $method->invoke($this->renderer, $field, null);
        $this->assertNull($value);
    }
}
