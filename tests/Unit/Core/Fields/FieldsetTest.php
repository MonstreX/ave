<?php

namespace Tests\Unit\Core\Fields;

use Tests\TestCase;
use Monstrex\Ave\Core\Fields\Fieldset;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Toggle;
use Monstrex\Ave\Core\Fields\Number;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Exceptions\FieldsetNestingException;
use Illuminate\Http\Request;

class FieldsetTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_key()
    {
        $fieldset = Fieldset::make('items');

        $this->assertInstanceOf(Fieldset::class, $fieldset);
        $this->assertEquals('items', $fieldset->key());
    }

    /** @test */
    public function it_can_configure_child_schema()
    {
        $fieldset = Fieldset::make('items')
            ->schema([
                TextInput::make('title'),
                Toggle::make('active'),
            ]);

        $schema = $fieldset->getChildSchema();

        $this->assertCount(2, $schema);
        $this->assertInstanceOf(TextInput::class, $schema[0]);
        $this->assertInstanceOf(Toggle::class, $schema[1]);
    }

    /** @test */
    public function it_can_configure_ui_options()
    {
        $fieldset = Fieldset::make('items')
            ->sortable(true)
            ->collapsible(true)
            ->minItems(1)
            ->maxItems(10)
            ->addButtonLabel('Add Item')
            ->deleteButtonLabel('Remove');

        $this->assertTrue($fieldset->isSortable());
        $this->assertTrue($fieldset->isCollapsible());
        $this->assertEquals(1, $fieldset->getMinItems());
        $this->assertEquals(10, $fieldset->getMaxItems());
        $this->assertEquals('Add Item', $fieldset->getAddButtonLabel());
        $this->assertEquals('Remove', $fieldset->getDeleteButtonLabel());
    }

    /** @test */
    public function it_builds_validation_rules_for_child_fields()
    {
        $fieldset = Fieldset::make('items')
            ->required()
            ->schema([
                TextInput::make('title')->required(),
                TextInput::make('description'),
            ]);

        $rules = $fieldset->buildValidationRules();

        $this->assertArrayHasKey('items', $rules);
        $this->assertArrayHasKey('items.*.title', $rules);
        $this->assertStringContainsString('required', $rules['items']);
        $this->assertStringContainsString('required', $rules['items.*.title']);
    }

    /** @test */
    public function it_prevents_nested_fieldsets()
    {
        $this->expectException(FieldsetNestingException::class);

        Fieldset::make('outer')
            ->schema([
                Fieldset::make('inner')
            ]);
    }

    /** @test */
    public function it_prepares_data_for_save()
    {
        $fieldset = Fieldset::make('items')
            ->schema([
                TextInput::make('title'),
                Toggle::make('active'),
            ]);

        $request = Request::create('/', 'POST', [
            'items' => [
                ['_id' => 0, 'title' => 'First', 'active' => true],
                ['_id' => 1, 'title' => 'Second', 'active' => false],
            ]
        ]);

        $context = FormContext::forCreate([], $request);
        $result = $fieldset->prepareForSave($request->input('items'), $request, $context);

        $this->assertIsArray($result->value());
        $this->assertCount(2, $result->value());
        $this->assertEquals('First', $result->value()[0]['title']);
    }

    /** @test */
    public function it_normalizes_item_ids()
    {
        $fieldset = Fieldset::make('items')
            ->schema([TextInput::make('title')]);

        $request = Request::create('/', 'POST', [
            'items' => [
                ['title' => 'First'],  // Missing _id
                ['_id' => 5, 'title' => 'Second'],
                ['title' => 'Third'],  // Missing _id
            ]
        ]);

        $context = FormContext::forCreate([], $request);
        $fieldset->prepareRequest($request, $context);

        $items = $request->input('items');

        // Ensure each item receives an _id
        foreach ($items as $item) {
            $this->assertArrayHasKey('_id', $item);
            $this->assertIsInt($item['_id']);
        }
    }

    /** @test */
    public function it_renders_with_context()
    {
        $fieldset = Fieldset::make('items')
            ->schema([
                TextInput::make('title')->label('Title'),
            ]);

        $context = FormContext::forCreate();
        $fieldset->setValue([
            ['_id' => 0, 'title' => 'Test Item']
        ]);

        $html = $fieldset->render($context);

        $this->assertStringContainsString('items', $html);
        $this->assertIsString($html);
    }

    /** @test */
    public function it_can_configure_head_title_and_preview()
    {
        $fieldset = Fieldset::make('items')
            ->headTitle('title')
            ->headPreview('description')
            ->schema([
                TextInput::make('title'),
                TextInput::make('description'),
            ]);

        $this->assertEquals('title', $fieldset->getHeadTitle());
        $this->assertEquals('description', $fieldset->getHeadPreview());
    }

    /** @test */
    public function it_can_configure_columns()
    {
        $fieldset = Fieldset::make('items')
            ->columns(4)
            ->schema([TextInput::make('title')]);

        $this->assertEquals(4, $fieldset->getColumns());
    }

    /** @test */
    public function it_flattens_fields_correctly()
    {
        $fieldset = Fieldset::make('items')
            ->schema([
                TextInput::make('title'),
                Number::make('price'),
            ]);

        $flattened = $fieldset->flattenFields();

        // Fieldset itself is returned, not its children
        $this->assertCount(1, $flattened);
        $this->assertSame($fieldset, $flattened[0]);
    }

    /** @test */
    public function it_gets_flattened_child_fields()
    {
        $fieldset = Fieldset::make('items')
            ->schema([
                TextInput::make('title'),
                Number::make('price'),
                Toggle::make('active'),
            ]);

        $children = $fieldset->getFlattenedChildFields();

        $this->assertCount(3, $children);
        $this->assertInstanceOf(TextInput::class, $children[0]);
        $this->assertInstanceOf(Number::class, $children[1]);
        $this->assertInstanceOf(Toggle::class, $children[2]);
    }

    /** @test */
    public function it_can_preserve_empty_items()
    {
        $fieldset = Fieldset::make('items')
            ->minItems(1)
            ->preserveEmptyItems()
            ->schema([
                TextInput::make('title'),
                TextInput::make('description'),
            ]);

        $request = Request::create('/', 'POST', [
            'items' => [
                ['_id' => 0, 'title' => '', 'description' => ''],
            ],
        ]);

        $context = FormContext::forCreate([], $request);
        $result = $fieldset->prepareForSave($request->input('items'), $request, $context);

        $this->assertCount(1, $result->value());
        $this->assertArrayHasKey('title', $result->value()[0]);
        $this->assertSame('', $result->value()[0]['title']);
    }
}
