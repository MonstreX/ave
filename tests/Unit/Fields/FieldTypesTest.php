<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Textarea;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Fields\Select;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\Fields\Hidden;
use Monstrex\Ave\Core\Fields\DateTimePicker;
use Monstrex\Ave\Core\Fields\FileUpload;
use Monstrex\Ave\Core\Fields\RichText;
use Monstrex\Ave\Core\Fields\Fieldset;

class FieldTypesTest extends TestCase
{
    public function test_text_input_field()
    {
        $field = TextInput::make('email')->maxLength(255);
        $array = $field->toArray();

        $this->assertEquals('text', $array['type']);
        $this->assertEquals(255, $array['maxLength']);
    }

    public function test_textarea_field()
    {
        $field = Textarea::make('description')->rows(5);
        $array = $field->toArray();

        $this->assertEquals('textarea', $array['type']);
        $this->assertEquals(5, $array['rows']);
    }

    public function test_toggle_field()
    {
        $field = Toggle::make('active');
        $array = $field->toArray();

        $this->assertEquals('toggle', $array['type']);
    }

    public function test_toggle_extract()
    {
        $field = Toggle::make('active');
        $this->assertTrue($field->extract('on'));
        $this->assertTrue($field->extract(true));
        $this->assertFalse($field->extract(null));
    }

    public function test_select_field()
    {
        $field = Select::make('role')
            ->options(['user' => 'User', 'admin' => 'Admin']);
        $array = $field->toArray();

        $this->assertEquals('select', $array['type']);
        $this->assertIsArray($array['options']);
    }

    public function test_number_field()
    {
        $field = Number::make('age')->min(0)->max(100);
        $array = $field->toArray();

        $this->assertEquals('number', $array['type']);
        $this->assertEquals(0, $array['min']);
        $this->assertEquals(100, $array['max']);
    }

    public function test_number_extract()
    {
        $field = Number::make('count');
        $this->assertEquals(42.0, $field->extract('42'));
        $this->assertNull($field->extract(null));
    }

    public function test_hidden_field()
    {
        $field = Hidden::make('token');
        $array = $field->toArray();

        $this->assertEquals('hidden', $array['type']);
    }

    public function test_datetime_field()
    {
        $field = DateTimePicker::make('created_at');
        $array = $field->toArray();

        $this->assertEquals('datetime', $array['type']);
        $this->assertTrue($array['withTime']);
    }

    public function test_file_upload_field()
    {
        $field = FileUpload::make('attachment')
            ->multiple()
            ->disk('s3')
            ->path('uploads/documents');
        $array = $field->toArray();

        $this->assertEquals('file', $array['type']);
        $this->assertTrue($array['multiple']);
        $this->assertEquals('s3', $array['disk']);
    }

    public function test_richtext_field()
    {
        $field = RichText::make('content')->fullHeight();
        $array = $field->toArray();

        $this->assertEquals('richtext', $array['type']);
        $this->assertTrue($array['fullHeight']);
    }

    public function test_fieldset_field()
    {
        $field = Fieldset::make('meta')
            ->schema([
                TextInput::make('key'),
                TextInput::make('value'),
            ])
            ->minRows(1)
            ->maxRows(10);
        $array = $field->toArray();

        $this->assertEquals('fieldset', $array['type']);
        $this->assertEquals(1, $array['minRows']);
        $this->assertEquals(10, $array['maxRows']);
        $this->assertIsArray($array['schema']);
    }
}
