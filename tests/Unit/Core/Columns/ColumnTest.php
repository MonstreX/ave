<?php

namespace Monstrex\Ave\Tests\Unit\Core\Columns;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Columns\Column;

/**
 * ColumnTest - Unit tests for Column class.
 *
 * Tests the Column class which handles:
 * - Column definition with key and label
 * - Sortable and searchable column configuration
 * - Column alignment and width settings
 * - Value formatting with callbacks
 * - Array serialization for API responses
 */
class ColumnTest extends TestCase
{
    private Column $column;

    protected function setUp(): void
    {
        parent::setUp();
        $this->column = new Column('id');
    }

    /**
     * Test column can be instantiated
     */
    public function test_column_can_be_instantiated(): void
    {
        $this->assertInstanceOf(Column::class, $this->column);
    }

    /**
     * Test column make factory method
     */
    public function test_column_make_factory_method(): void
    {
        $column = Column::make('name');
        $this->assertInstanceOf(Column::class, $column);
        $this->assertEquals('name', $column->key());
    }

    /**
     * Test column key method returns correct key
     */
    public function test_column_key_method(): void
    {
        $column = Column::make('email');
        $this->assertEquals('email', $column->key());
    }

    /**
     * Test column label method is fluent
     */
    public function test_column_label_method_is_fluent(): void
    {
        $result = $this->column->label('ID');
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column label can be set and retrieved
     */
    public function test_column_label_can_be_set(): void
    {
        $this->column->label('Identifier');
        $this->assertEquals('Identifier', $this->column->getLabel());
    }

    /**
     * Test column default label generation
     */
    public function test_column_default_label_generation(): void
    {
        $column = Column::make('user_name');
        $this->assertEquals('User name', $column->getLabel());
    }

    /**
     * Test column sortable method is fluent
     */
    public function test_column_sortable_method_is_fluent(): void
    {
        $result = $this->column->sortable();
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column sortable can be enabled
     */
    public function test_column_sortable_can_be_enabled(): void
    {
        $this->column->sortable(true);
        $this->assertTrue($this->column->isSortable());
    }

    /**
     * Test column sortable can be disabled
     */
    public function test_column_sortable_can_be_disabled(): void
    {
        $this->column->sortable(false);
        $this->assertFalse($this->column->isSortable());
    }

    /**
     * Test column sortable defaults to false
     */
    public function test_column_sortable_default(): void
    {
        $this->assertFalse($this->column->isSortable());
    }

    /**
     * Test column searchable method is fluent
     */
    public function test_column_searchable_method_is_fluent(): void
    {
        $result = $this->column->searchable();
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column searchable can be enabled
     */
    public function test_column_searchable_can_be_enabled(): void
    {
        $this->column->searchable(true);
        $this->assertTrue($this->column->isSearchable());
    }

    /**
     * Test column searchable can be disabled
     */
    public function test_column_searchable_can_be_disabled(): void
    {
        $this->column->searchable(false);
        $this->assertFalse($this->column->isSearchable());
    }

    /**
     * Test column searchable defaults to false
     */
    public function test_column_searchable_default(): void
    {
        $this->assertFalse($this->column->isSearchable());
    }

    /**
     * Test column hidden method is fluent
     */
    public function test_column_hidden_method_is_fluent(): void
    {
        $result = $this->column->hidden();
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column hidden can be enabled
     */
    public function test_column_hidden_can_be_enabled(): void
    {
        $this->column->hidden(true);
        $this->assertTrue($this->column->toArray()['hidden']);
    }

    /**
     * Test column hidden can be disabled
     */
    public function test_column_hidden_can_be_disabled(): void
    {
        $this->column->hidden(false);
        $this->assertFalse($this->column->toArray()['hidden']);
    }

    /**
     * Test column align method is fluent
     */
    public function test_column_align_method_is_fluent(): void
    {
        $result = $this->column->align('center');
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column align can be set
     */
    public function test_column_align_can_be_set(): void
    {
        $this->column->align('right');
        $this->assertEquals('right', $this->column->toArray()['align']);
    }

    /**
     * Test column align defaults to left
     */
    public function test_column_align_default(): void
    {
        $this->assertEquals('left', $this->column->toArray()['align']);
    }

    /**
     * Test column width method is fluent
     */
    public function test_column_width_method_is_fluent(): void
    {
        $result = $this->column->width(100);
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column width can be set
     */
    public function test_column_width_can_be_set(): void
    {
        $this->column->width(200);
        $this->assertEquals(200, $this->column->toArray()['width']);
    }

    /**
     * Test column width can be null
     */
    public function test_column_width_can_be_null(): void
    {
        $this->assertNull($this->column->toArray()['width']);
    }

    /**
     * Test column format method is fluent
     */
    public function test_column_format_method_is_fluent(): void
    {
        $result = $this->column->format(fn($v) => $v);
        $this->assertInstanceOf(Column::class, $result);
        $this->assertSame($this->column, $result);
    }

    /**
     * Test column format callback is applied
     */
    public function test_column_format_callback_applied(): void
    {
        $this->column->format(fn($v, $r) => strtoupper($v));
        $result = $this->column->formatValue('hello', null);
        $this->assertEquals('HELLO', $result);
    }

    /**
     * Test column format value without callback
     */
    public function test_column_format_value_without_callback(): void
    {
        $value = 'test';
        $result = $this->column->formatValue($value, null);
        $this->assertEquals('test', $result);
    }

    /**
     * Test column format callback receives record
     */
    public function test_column_format_callback_receives_record(): void
    {
        $this->column->format(function($v, $r) {
            return $r['id'] . ': ' . $v;
        });

        $result = $this->column->formatValue('name', ['id' => 1]);
        $this->assertEquals('1: name', $result);
    }

    /**
     * Test column toArray method
     */
    public function test_column_to_array_method(): void
    {
        $array = $this->column->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('key', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('sortable', $array);
        $this->assertArrayHasKey('searchable', $array);
        $this->assertArrayHasKey('hidden', $array);
        $this->assertArrayHasKey('align', $array);
        $this->assertArrayHasKey('width', $array);
    }

    /**
     * Test column toArray includes all set properties
     */
    public function test_column_to_array_includes_all_properties(): void
    {
        $this->column
            ->label('ID Column')
            ->sortable(true)
            ->searchable(true)
            ->hidden(false)
            ->align('center')
            ->width(150);

        $array = $this->column->toArray();

        $this->assertEquals('id', $array['key']);
        $this->assertEquals('ID Column', $array['label']);
        $this->assertTrue($array['sortable']);
        $this->assertTrue($array['searchable']);
        $this->assertFalse($array['hidden']);
        $this->assertEquals('center', $array['align']);
        $this->assertEquals(150, $array['width']);
    }

    /**
     * Test column fluent interface chaining
     */
    public function test_column_fluent_interface_chaining(): void
    {
        $result = Column::make('title')
            ->label('Article Title')
            ->sortable(true)
            ->searchable(true)
            ->width(300)
            ->align('left');

        $this->assertInstanceOf(Column::class, $result);
        $this->assertEquals('title', $result->key());
        $this->assertTrue($result->isSortable());
        $this->assertTrue($result->isSearchable());
    }

    /**
     * Test multiple column instances
     */
    public function test_multiple_column_instances(): void
    {
        $col1 = Column::make('id')->sortable();
        $col2 = Column::make('name')->searchable();
        $col3 = Column::make('email');

        $this->assertTrue($col1->isSortable());
        $this->assertFalse($col1->isSearchable());

        $this->assertFalse($col2->isSortable());
        $this->assertTrue($col2->isSearchable());

        $this->assertFalse($col3->isSortable());
        $this->assertFalse($col3->isSearchable());
    }

    /**
     * Test column label with special characters
     */
    public function test_column_label_with_special_characters(): void
    {
        $this->column->label('ID (Primary Key)');
        $this->assertEquals('ID (Primary Key)', $this->column->getLabel());
    }

    /**
     * Test column format with complex callback
     */
    public function test_column_format_with_complex_callback(): void
    {
        $this->column->format(function($value, $record) {
            if ($value === null) {
                return 'N/A';
            }
            if ($value === true) {
                return 'Yes';
            }
            if ($value === false) {
                return 'No';
            }
            return $value;
        });

        $this->assertEquals('N/A', $this->column->formatValue(null, []));
        $this->assertEquals('Yes', $this->column->formatValue(true, []));
        $this->assertEquals('No', $this->column->formatValue(false, []));
        $this->assertEquals('Value', $this->column->formatValue('Value', []));
    }

    /**
     * Test column width with different values
     */
    public function test_column_width_with_different_values(): void
    {
        $widths = [50, 100, 200, 500, 1000];

        foreach ($widths as $width) {
            $column = Column::make('test')->width($width);
            $this->assertEquals($width, $column->toArray()['width']);
        }
    }

    public function test_column_resolves_dot_notation_values(): void
    {
        $column = Column::make('author.name');
        $record = (object) [
            'author' => (object) ['name' => 'Ave'],
        ];

        $this->assertEquals('Ave', $column->resolveRecordValue($record));
    }

    public function test_column_inline_configuration(): void
    {
        $column = Column::make('status')->inline('toggle')->inlineRules('boolean');

        $this->assertTrue($column->supportsInline());
        $this->assertEquals('status', $column->inlineField());
        $this->assertEquals('toggle', $column->inlineMode());
        $this->assertEquals('boolean', $column->inlineValidationRules());
    }

    /**
     * Test column align with different values
     */
    public function test_column_align_with_different_values(): void
    {
        $aligns = ['left', 'center', 'right'];

        foreach ($aligns as $align) {
            $column = Column::make('test')->align($align);
            $this->assertEquals($align, $column->toArray()['align']);
        }
    }

    /**
     * Test column method visibility
     */
    public function test_column_methods_are_public(): void
    {
        $reflection = new \ReflectionClass($this->column);

        $publicMethods = [
            'key',
            'label',
            'sortable',
            'searchable',
            'hidden',
            'align',
            'width',
            'format',
            'getLabel',
            'formatValue',
            'isSortable',
            'isSearchable',
            'toArray'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Column should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test column has make static method
     */
    public function test_column_has_make_static_method(): void
    {
        $reflection = new \ReflectionClass($this->column);
        $this->assertTrue($reflection->hasMethod('make'));
        $this->assertTrue($reflection->getMethod('make')->isStatic());
    }

    /**
     * Test column namespace
     */
    public function test_column_namespace(): void
    {
        $reflection = new \ReflectionClass($this->column);
        $this->assertEquals('Monstrex\\Ave\\Core\\Columns', $reflection->getNamespaceName());
    }

    /**
     * Test column class name
     */
    public function test_column_class_name(): void
    {
        $reflection = new \ReflectionClass($this->column);
        $this->assertEquals('Column', $reflection->getShortName());
    }

}
