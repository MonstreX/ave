<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\TextInput;

class AbstractFieldTest extends TestCase
{
    protected TextInput $field;

    protected function setUp(): void
    {
        $this->field = TextInput::make('username');
    }

    public function test_field_can_be_created()
    {
        $this->assertInstanceOf(TextInput::class, $this->field);
    }

    public function test_field_fluent_interface()
    {
        $result = $this->field->label('Username')
            ->required()
            ->placeholder('Enter your username');

        $this->assertInstanceOf(TextInput::class, $result);
    }

    public function test_field_key()
    {
        $this->assertEquals('username', $this->field->key());
    }

    public function test_field_type()
    {
        $this->assertEquals('text', $this->field->type());
    }

    public function test_field_label()
    {
        $this->field->label('Username');
        $this->assertEquals('Username', $this->field->getLabel());
    }

    public function test_field_is_required()
    {
        $this->assertFalse($this->field->isRequired());
        $this->field->required(true);
        $this->assertTrue($this->field->isRequired());
    }

    public function test_field_to_array()
    {
        $this->field->label('Username')->required();
        $array = $this->field->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('username', $array['key']);
        $this->assertEquals('text', $array['type']);
        $this->assertTrue($array['required']);
    }

    public function test_field_extract()
    {
        $value = $this->field->extract('test');
        $this->assertEquals('test', $value);
    }
}
