<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Fieldset;
use PHPUnit\Framework\TestCase;

/**
 * Test that Row/Col are now fully supported in Fieldset schema
 *
 * ItemFactory now handles Row/Col containers by:
 * 1. Detecting Row instances in schema
 * 2. Extracting columns and fields
 * 3. Processing each field (state path, data loading, etc.)
 * 4. Returning Row with processed columns for rendering
 */
class FieldsetRowColSupportTest extends TestCase
{
    /**
     * Test: Fieldset with Row/Col now works properly
     */
    public function test_fieldset_supports_row_col_in_schema()
    {
        $fieldset = Fieldset::make('items')->schema([
            Row::make()->columns([
                Col::make(6)->fields([
                    TextInput::make('name')->label('Name'),
                ]),
                Col::make(6)->fields([
                    TextInput::make('value')->label('Value'),
                ]),
            ]),
        ]);

        $childSchema = $fieldset->getChildSchema();

        // Row is in the schema
        $this->assertCount(1, $childSchema);
        $this->assertInstanceOf(Row::class, $childSchema[0]);

        // ItemFactory now processes Row/Col properly
        $itemFactory = new \Monstrex\Ave\Core\Fields\Fieldset\ItemFactory($fieldset);
        $itemData = [
            '_id' => 0,
            'name' => 'Test Name',
            'value' => 'Test Value',
        ];
        $item = $itemFactory->makeFromData(0, $itemData, null);

        // Result: Row object is in fields with processed columns and fields
        $this->assertCount(1, $item->fields);
        $this->assertInstanceOf(Row::class, $item->fields[0]);

        // Check that Row contains 2 columns
        $row = $item->fields[0];
        $this->assertCount(2, $row->getColumns());

        // Check that each column has 1 processed field
        $col1 = $row->getColumns()[0];
        $col2 = $row->getColumns()[1];
        $this->assertCount(1, $col1->getFields());
        $this->assertCount(1, $col2->getFields());

        // Check that fields are TextInput instances
        $this->assertInstanceOf(TextInput::class, $col1->getFields()[0]);
        $this->assertInstanceOf(TextInput::class, $col2->getFields()[0]);

        // Check that fields have correct data
        $this->assertEquals('Test Name', $col1->getFields()[0]->getValue());
        $this->assertEquals('Test Value', $col2->getFields()[0]->getValue());
    }

    /**
     * Test: Fieldset still works with flat field list
     */
    public function test_fieldset_works_with_flat_fields()
    {
        $fieldset = Fieldset::make('items')->schema([
            TextInput::make('name')->label('Name'),
            TextInput::make('value')->label('Value'),
        ]);

        $itemFactory = new \Monstrex\Ave\Core\Fields\Fieldset\ItemFactory($fieldset);
        $itemData = [
            '_id' => 0,
            'name' => 'Test Name',
            'value' => 'Test Value',
        ];
        $item = $itemFactory->makeFromData(0, $itemData, null);

        // Result: all fields are present (backward compatible)
        $this->assertCount(2, $item->fields);
        $this->assertInstanceOf(TextInput::class, $item->fields[0]);
        $this->assertInstanceOf(TextInput::class, $item->fields[1]);
    }

    /**
     * Test: Row/Col with multiple columns and various spans
     */
    public function test_fieldset_row_col_with_different_spans()
    {
        $fieldset = Fieldset::make('items')->schema([
            Row::make()->columns([
                Col::make(4)->fields([
                    TextInput::make('col1')->label('Column 1'),
                ]),
                Col::make(4)->fields([
                    TextInput::make('col2')->label('Column 2'),
                ]),
                Col::make(4)->fields([
                    TextInput::make('col3')->label('Column 3'),
                ]),
            ]),
        ]);

        $itemFactory = new \Monstrex\Ave\Core\Fields\Fieldset\ItemFactory($fieldset);
        $itemData = [
            '_id' => 0,
            'col1' => 'Value 1',
            'col2' => 'Value 2',
            'col3' => 'Value 3',
        ];
        $item = $itemFactory->makeFromData(0, $itemData, null);

        // Check Row structure
        $this->assertCount(1, $item->fields);
        $row = $item->fields[0];
        $columns = $row->getColumns();

        // Check 3 columns with correct spans
        $this->assertCount(3, $columns);
        $this->assertEquals(4, $columns[0]->getSpan());
        $this->assertEquals(4, $columns[1]->getSpan());
        $this->assertEquals(4, $columns[2]->getSpan());

        // Check all fields are processed
        $this->assertEquals('Value 1', $columns[0]->getFields()[0]->getValue());
        $this->assertEquals('Value 2', $columns[1]->getFields()[0]->getValue());
        $this->assertEquals('Value 3', $columns[2]->getFields()[0]->getValue());
    }
}
