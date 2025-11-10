<?php

namespace Monstrex\Ave\Tests\Unit\Core\Components;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Components\Group;
use Monstrex\Ave\Core\Components\RowComponent;
use Monstrex\Ave\Core\Row;
use Monstrex\Ave\Core\Col;

/**
 * GroupAndRowTest - Unit tests for Group and RowComponent components.
 *
 * Tests the Group and RowComponent which provide:
 * - Semantic field grouping with labels and descriptions
 * - Row-based layout management
 * - Field flattening for validation
 * - Layout array conversion for rendering
 * - Fluent interface for configuration
 */
class GroupAndRowTest extends TestCase
{
    /**
     * Test group can be instantiated
     */
    public function test_group_can_be_instantiated(): void
    {
        $group = new Group();
        $this->assertInstanceOf(Group::class, $group);
    }

    /**
     * Test group make factory method without label
     */
    public function test_group_make_factory_without_label(): void
    {
        $group = Group::make();
        $this->assertInstanceOf(Group::class, $group);
        $this->assertNull($group->getLabel());
    }

    /**
     * Test group make factory method with label
     */
    public function test_group_make_factory_with_label(): void
    {
        $group = Group::make('Security Settings');
        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Security Settings', $group->getLabel());
    }

    /**
     * Test group label method is fluent
     */
    public function test_group_label_method_is_fluent(): void
    {
        $group = new Group();
        $result = $group->label('Account Settings');

        $this->assertInstanceOf(Group::class, $result);
        $this->assertSame($group, $result);
    }

    /**
     * Test group label can be set
     */
    public function test_group_label_can_be_set(): void
    {
        $group = Group::make()->label('Personal Info');
        $this->assertEquals('Personal Info', $group->getLabel());
    }

    /**
     * Test group label can be null
     */
    public function test_group_label_can_be_null(): void
    {
        $group = Group::make('Initial Label')->label(null);
        $this->assertNull($group->getLabel());
    }

    /**
     * Test group label defaults to null
     */
    public function test_group_label_default_null(): void
    {
        $group = new Group();
        $this->assertNull($group->getLabel());
    }

    /**
     * Test group description method is fluent
     */
    public function test_group_description_method_is_fluent(): void
    {
        $group = new Group();
        $result = $group->description('This section contains security options');

        $this->assertInstanceOf(Group::class, $result);
        $this->assertSame($group, $result);
    }

    /**
     * Test group description can be set
     */
    public function test_group_description_can_be_set(): void
    {
        $group = Group::make('Settings')
            ->description('Configure your preferences here');

        $this->assertEquals('Configure your preferences here', $group->getDescription());
    }

    /**
     * Test group description can be null
     */
    public function test_group_description_can_be_null(): void
    {
        $group = Group::make('Settings')
            ->description('Initial description')
            ->description(null);

        $this->assertNull($group->getDescription());
    }

    /**
     * Test group description defaults to null
     */
    public function test_group_description_default_null(): void
    {
        $group = new Group();
        $this->assertNull($group->getDescription());
    }

    /**
     * Test group fluent interface chaining
     */
    public function test_group_fluent_interface_chaining(): void
    {
        $group = Group::make('Advanced Options')
            ->description('For advanced users only')
            ->label('Expert Settings');

        $this->assertEquals('Expert Settings', $group->getLabel());
        $this->assertEquals('For advanced users only', $group->getDescription());
    }

    /**
     * Test group make with components
     */
    public function test_group_make_with_components(): void
    {
        $group = Group::make('Group Label', []);
        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Group Label', $group->getLabel());
    }

    /**
     * Test group schema method
     */
    public function test_group_schema_method(): void
    {
        $group = Group::make('Settings')->schema([]);
        $this->assertInstanceOf(Group::class, $group);
    }

    /**
     * Test multiple group instances
     */
    public function test_multiple_group_instances(): void
    {
        $group1 = Group::make('Group 1');
        $group2 = Group::make('Group 2');

        $this->assertNotSame($group1, $group2);
        $this->assertEquals('Group 1', $group1->getLabel());
        $this->assertEquals('Group 2', $group2->getLabel());
    }

    /**
     * Test group with empty label
     */
    public function test_group_with_empty_label(): void
    {
        $group = Group::make('');
        $this->assertEquals('', $group->getLabel());
    }

    /**
     * Test group with special characters in label
     */
    public function test_group_with_special_characters(): void
    {
        $group = Group::make('User & Account Settings');
        $this->assertEquals('User & Account Settings', $group->getLabel());
    }

    /**
     * Test group with long description
     */
    public function test_group_with_long_description(): void
    {
        $longDescription = 'This is a very long description that explains what the group is for and provides guidance to users about the options available in this section.';
        $group = Group::make('Settings')->description($longDescription);
        $this->assertEquals($longDescription, $group->getDescription());
    }

    /**
     * Test group method visibility
     */
    public function test_group_methods_are_public(): void
    {
        $group = new Group();
        $reflection = new \ReflectionClass($group);

        $publicMethods = [
            'make',
            'label',
            'description',
            'getLabel',
            'getDescription',
            'schema'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Group should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test group namespace
     */
    public function test_group_namespace(): void
    {
        $group = new Group();
        $reflection = new \ReflectionClass($group);
        $this->assertEquals('Monstrex\\Ave\\Core\\Components', $reflection->getNamespaceName());
    }

    /**
     * Test group class name
     */
    public function test_group_class_name(): void
    {
        $group = new Group();
        $reflection = new \ReflectionClass($group);
        $this->assertEquals('Group', $reflection->getShortName());
    }

    /**
     * Test row component can be instantiated
     */
    public function test_row_component_can_be_instantiated(): void
    {
        $row = $this->createMock(Row::class);
        $component = new RowComponent($row);

        $this->assertInstanceOf(RowComponent::class, $component);
    }

    /**
     * Test row component from row factory
     */
    public function test_row_component_from_row_factory(): void
    {
        $row = $this->createMock(Row::class);
        $component = RowComponent::fromRow($row);

        $this->assertInstanceOf(RowComponent::class, $component);
    }

    /**
     * Test row component get row
     */
    public function test_row_component_get_row(): void
    {
        $row = $this->createMock(Row::class);
        $component = new RowComponent($row);

        $this->assertSame($row, $component->getRow());
    }

    /**
     * Test row component get row after factory
     */
    public function test_row_component_get_row_after_factory(): void
    {
        $row = $this->createMock(Row::class);
        $component = RowComponent::fromRow($row);

        $this->assertSame($row, $component->getRow());
    }

    /**
     * Test row component flatten fields empty
     */
    public function test_row_component_flatten_fields_empty(): void
    {
        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([]);

        $component = new RowComponent($row);
        $fields = $component->flattenFields();

        $this->assertIsArray($fields);
        $this->assertEmpty($fields);
    }

    /**
     * Test row component flatten fields with columns
     */
    public function test_row_component_flatten_fields_with_columns(): void
    {
        $col = $this->createMock(Col::class);
        $col->method('getFields')->willReturn([
            'field1' => 'value1',
            'field2' => 'value2'
        ]);

        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([$col]);

        $component = new RowComponent($row);
        $fields = $component->flattenFields();

        $this->assertIsArray($fields);
        $this->assertCount(2, $fields);
    }

    /**
     * Test row component flatten fields multiple columns
     */
    public function test_row_component_flatten_fields_multiple_columns(): void
    {
        $col1 = $this->createMock(Col::class);
        $col1->method('getFields')->willReturn(['field1', 'field2']);

        $col2 = $this->createMock(Col::class);
        $col2->method('getFields')->willReturn(['field3', 'field4']);

        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([$col1, $col2]);

        $component = new RowComponent($row);
        $fields = $component->flattenFields();

        $this->assertCount(4, $fields);
    }

    /**
     * Test row component to layout array
     */
    public function test_row_component_to_layout_array(): void
    {
        $col = $this->createMock(Col::class);
        $col->method('getSpan')->willReturn(6);
        $col->method('getFields')->willReturn([]);

        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([$col]);

        $component = new RowComponent($row);
        $layout = $component->toLayoutArray();

        $this->assertIsArray($layout);
        $this->assertArrayHasKey('type', $layout);
        $this->assertEquals('row', $layout['type']);
        $this->assertArrayHasKey('columns', $layout);
        $this->assertArrayHasKey('component', $layout);
    }

    /**
     * Test row component to layout array structure
     */
    public function test_row_component_to_layout_array_structure(): void
    {
        $col = $this->createMock(Col::class);
        $col->method('getSpan')->willReturn(12);
        $col->method('getFields')->willReturn(['field1']);

        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([$col]);

        $component = new RowComponent($row);
        $layout = $component->toLayoutArray();

        $this->assertEquals('row', $layout['type']);
        $this->assertCount(1, $layout['columns']);
        $this->assertEquals(12, $layout['columns'][0]['span']);
        $this->assertSame($component, $layout['component']);
    }

    /**
     * Test row component to layout array with multiple columns
     */
    public function test_row_component_to_layout_array_multiple_columns(): void
    {
        $col1 = $this->createMock(Col::class);
        $col1->method('getSpan')->willReturn(6);
        $col1->method('getFields')->willReturn([]);

        $col2 = $this->createMock(Col::class);
        $col2->method('getSpan')->willReturn(6);
        $col2->method('getFields')->willReturn([]);

        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([$col1, $col2]);

        $component = new RowComponent($row);
        $layout = $component->toLayoutArray();

        $this->assertCount(2, $layout['columns']);
        $this->assertEquals(6, $layout['columns'][0]['span']);
        $this->assertEquals(6, $layout['columns'][1]['span']);
    }

    /**
     * Test row component multiple instances
     */
    public function test_row_component_multiple_instances(): void
    {
        $row1 = $this->createMock(Row::class);
        $row2 = $this->createMock(Row::class);

        $component1 = new RowComponent($row1);
        $component2 = new RowComponent($row2);

        $this->assertNotSame($component1, $component2);
        $this->assertSame($row1, $component1->getRow());
        $this->assertSame($row2, $component2->getRow());
    }

    /**
     * Test row component flatten fields is array
     */
    public function test_row_component_flatten_fields_returns_array(): void
    {
        $row = $this->createMock(Row::class);
        $row->method('getColumns')->willReturn([]);

        $component = new RowComponent($row);
        $fields = $component->flattenFields();

        $this->assertIsArray($fields);
    }

    /**
     * Test row component method visibility
     */
    public function test_row_component_methods_public(): void
    {
        $row = $this->createMock(Row::class);
        $component = new RowComponent($row);

        $reflection = new \ReflectionClass($component);

        $publicMethods = [
            'fromRow',
            'getRow',
            'flattenFields',
            'toLayoutArray'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "RowComponent should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test row component namespace
     */
    public function test_row_component_namespace(): void
    {
        $row = $this->createMock(Row::class);
        $component = new RowComponent($row);

        $reflection = new \ReflectionClass($component);
        $this->assertEquals('Monstrex\\Ave\\Core\\Components', $reflection->getNamespaceName());
    }

    /**
     * Test row component class name
     */
    public function test_row_component_class_name(): void
    {
        $row = $this->createMock(Row::class);
        $component = new RowComponent($row);

        $reflection = new \ReflectionClass($component);
        $this->assertEquals('RowComponent', $reflection->getShortName());
    }

    /**
     * Test row component from row static method
     */
    public function test_row_component_from_row_static(): void
    {
        $row = $this->createMock(Row::class);

        $reflection = new \ReflectionClass(RowComponent::class);
        $method = $reflection->getMethod('fromRow');

        $this->assertTrue($method->isStatic());
    }

    /**
     * Test group label with newlines
     */
    public function test_group_label_with_newlines(): void
    {
        $label = "Multi\nLine\nLabel";
        $group = Group::make($label);
        $this->assertEquals($label, $group->getLabel());
    }

    /**
     * Test group description with markdown
     */
    public function test_group_description_with_markdown(): void
    {
        $description = "This is **bold** and *italic* text";
        $group = Group::make('Settings')->description($description);
        $this->assertEquals($description, $group->getDescription());
    }
}
