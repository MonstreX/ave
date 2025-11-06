<?php

namespace Monstrex\Ave\Tests\Unit\Fields;

use Monstrex\Ave\Core\Col;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Fields\TextInput;
use Monstrex\Ave\Core\Fields\Fieldset;
use PHPUnit\Framework\TestCase;

/**
 * Test that demonstrates Row/Col limitations in Fieldset schema
 *
 * Row and Col are not supported in Fieldset because ItemFactory checks:
 * if (!$definition instanceof AbstractField) { continue; }
 *
 * Row and Col don't extend AbstractField, so they are silently skipped.
 */
class FieldsetRowColSupportTest extends TestCase
{
    /**
     * Test: Fieldset with Row/Col silently ignores them
     */
    public function test_fieldset_ignores_row_col_in_schema()
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

        // Row is in the schema but...
        $this->assertCount(1, $childSchema);
        $this->assertInstanceOf(Row::class, $childSchema[0]);

        // ...ItemFactory will skip it because Row doesn't extend AbstractField
        $itemFactory = new \Monstrex\Ave\Core\Fields\Fieldset\ItemFactory($fieldset);
        $itemData = [
            '_id' => 0,
            'name' => 'Test Name',
            'value' => 'Test Value',
        ];
        $item = $itemFactory->makeFromData(0, $itemData, null);

        // Result: no fields in the item (Row was skipped)
        $this->assertCount(0, $item->fields);
    }

    /**
     * Test: Fieldset properly handles flat field list
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

        // Result: all fields are present
        $this->assertCount(2, $item->fields);
        $this->assertInstanceOf(TextInput::class, $item->fields[0]);
        $this->assertInstanceOf(TextInput::class, $item->fields[1]);
    }

    /**
     * Test: Demonstrate the difference
     *
     * This test shows why Row/Col don't work in Fieldset:
     *
     * ❌ Wrong (Row/Col ignored):
     *   Fieldset::make('items')->schema([
     *       Row::make()->columns([Col::make(6)->fields([...]), ...]),
     *   ])
     *
     * ✅ Correct (flat fields):
     *   Fieldset::make('items')->schema([
     *       TextInput::make('name'),
     *       TextInput::make('value'),
     *   ])
     */
    public function test_row_col_cannot_be_used_in_fieldset_schema()
    {
        // ItemFactory only processes AbstractField instances
        $fieldset = Fieldset::make('items');

        // Simulate what ItemFactory does
        $definitions = [
            Row::make()->columns([
                Col::make(6)->fields([TextInput::make('name')]),
            ]),
        ];

        $processedFields = array_filter(
            $definitions,
            fn ($def) => $def instanceof \Monstrex\Ave\Core\Fields\AbstractField
        );

        // Row is not AbstractField, so it gets filtered out
        $this->assertCount(0, $processedFields);
        $this->assertCount(1, $definitions); // But it's still in the original list
    }
}
