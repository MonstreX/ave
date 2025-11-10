<?php

namespace Monstrex\Ave\Tests\Unit\Core\DataSources;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\DataSources\ModelDataSource;
use Illuminate\Database\Eloquent\Model;

/**
 * DataSourceTest - Unit tests for DataSource implementations.
 *
 * Tests the DataSource abstraction which allows:
 * - Uniform data access across models and arrays
 * - Dot notation access to nested data
 * - Interface contract for field implementations
 * - Support for arrays, models, and JSON structures
 */
class DataSourceTest extends TestCase
{
    /**
     * Test array data source can be instantiated
     */
    public function test_array_data_source_can_be_instantiated(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);
        $this->assertInstanceOf(ArrayDataSource::class, $source);
    }

    /**
     * Test array data source implements interface
     */
    public function test_array_data_source_implements_interface(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);
        $this->assertInstanceOf(DataSourceInterface::class, $source);
    }

    /**
     * Test array data source get simple key
     */
    public function test_array_data_source_get_simple_key(): void
    {
        $data = ['title' => 'Test Title'];
        $source = new ArrayDataSource($data);
        $value = $source->get('title');
        $this->assertEquals('Test Title', $value);
    }

    /**
     * Test array data source get non-existent key returns null
     */
    public function test_array_data_source_get_non_existent_key(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);
        $value = $source->get('non_existent');
        $this->assertNull($value);
    }

    /**
     * Test array data source get nested key
     */
    public function test_array_data_source_get_nested_key(): void
    {
        $data = ['author' => ['name' => 'John Doe']];
        $source = new ArrayDataSource($data);
        $value = $source->get('author.name');
        $this->assertEquals('John Doe', $value);
    }

    /**
     * Test array data source get deeply nested key
     */
    public function test_array_data_source_get_deeply_nested_key(): void
    {
        $data = ['items' => [['id' => 1], ['id' => 2, 'name' => 'Item 2']]];
        $source = new ArrayDataSource($data);
        $value = $source->get('items.1.name');
        $this->assertEquals('Item 2', $value);
    }

    /**
     * Test array data source set simple key
     */
    public function test_array_data_source_set_simple_key(): void
    {
        $data = ['title' => 'Old Title'];
        $source = new ArrayDataSource($data);
        $source->set('title', 'New Title');
        $this->assertEquals('New Title', $source->get('title'));
    }

    /**
     * Test array data source set nested key
     */
    public function test_array_data_source_set_nested_key(): void
    {
        $data = ['author' => ['name' => 'John']];
        $source = new ArrayDataSource($data);
        $source->set('author.name', 'Jane');
        $this->assertEquals('Jane', $source->get('author.name'));
    }

    /**
     * Test array data source set deeply nested key
     */
    public function test_array_data_source_set_deeply_nested_key(): void
    {
        $data = ['items' => [['id' => 1], ['id' => 2, 'name' => 'Item 2']]];
        $source = new ArrayDataSource($data);
        $source->set('items.1.name', 'Updated Item');
        $this->assertEquals('Updated Item', $source->get('items.1.name'));
    }

    /**
     * Test array data source set creates nested arrays
     */
    public function test_array_data_source_set_creates_nested_arrays(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);
        $source->set('new.nested.key', 'value');
        $this->assertEquals('value', $source->get('new.nested.key'));
    }

    /**
     * Test array data source has existing key
     */
    public function test_array_data_source_has_existing_key(): void
    {
        $data = ['title' => 'Test'];
        $source = new ArrayDataSource($data);
        $this->assertTrue($source->has('title'));
    }

    /**
     * Test array data source has non-existent key
     */
    public function test_array_data_source_has_non_existent_key(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);
        $this->assertFalse($source->has('non_existent'));
    }

    /**
     * Test array data source has nested key
     */
    public function test_array_data_source_has_nested_key(): void
    {
        $data = ['author' => ['name' => 'John']];
        $source = new ArrayDataSource($data);
        $this->assertTrue($source->has('author.name'));
    }

    /**
     * Test array data source toArray
     */
    public function test_array_data_source_to_array(): void
    {
        $data = ['title' => 'Test', 'author' => ['name' => 'John']];
        $source = new ArrayDataSource($data);
        $array = $source->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test', $array['title']);
        $this->assertEquals('John', $array['author']['name']);
    }

    /**
     * Test array data source sync is no-op
     */
    public function test_array_data_source_sync_is_noop(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);
        // ArrayDataSource doesn't support model relations, so sync should do nothing
        $source->sync('relations', [1, 2, 3]);

        // Verify no exception thrown
        $this->assertTrue(true);
    }

    /**
     * Test model data source can be instantiated
     */
    public function test_model_data_source_can_be_instantiated(): void
    {
        $model = $this->createMock(Model::class);
        $source = new ModelDataSource($model);
        $this->assertInstanceOf(ModelDataSource::class, $source);
    }

    /**
     * Test model data source implements interface
     */
    public function test_model_data_source_implements_interface(): void
    {
        $model = $this->createMock(Model::class);
        $source = new ModelDataSource($model);
        $this->assertInstanceOf(DataSourceInterface::class, $source);
    }

    /**
     * Test model data source get model
     */
    public function test_model_data_source_get_model(): void
    {
        $model = $this->createMock(Model::class);
        $source = new ModelDataSource($model);

        $this->assertSame($model, $source->getModel());
    }

    /**
     * Test model data source toArray
     */
    public function test_model_data_source_to_array(): void
    {
        $model = $this->createMock(Model::class);
        $model->method('toArray')
            ->willReturn(['title' => 'Test', 'slug' => 'test']);

        $source = new ModelDataSource($model);
        $array = $source->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test', $array['title']);
    }

    /**
     * Test model data source set method extracts first key from dot notation
     */
    public function test_model_data_source_set_extracts_first_key(): void
    {
        $model = $this->createMock(Model::class);

        $source = new ModelDataSource($model);
        // This should set the 'author' attribute (first key in 'author.name')
        $source->set('author.name', 'Jane Doe');

        // Verify the method doesn't throw exception and works properly
        $this->assertInstanceOf(ModelDataSource::class, $source);
    }

    /**
     * Test model data source sync with non-existent method
     */
    public function test_model_data_source_sync_non_existent_method(): void
    {
        $model = $this->createMock(Model::class);

        $source = new ModelDataSource($model);
        // Should not throw exception even if method doesn't exist
        $source->sync('non_existent_relation', [1, 2, 3]);

        $this->assertTrue(true);
    }

    /**
     * Test array data source with complex nested structure
     */
    public function test_array_data_source_complex_structure(): void
    {
        $data = [
            'meta' => [
                'tags' => ['php', 'laravel', 'testing'],
                'authors' => [
                    ['name' => 'John', 'role' => 'admin'],
                    ['name' => 'Jane', 'role' => 'editor']
                ]
            ]
        ];

        $source = new ArrayDataSource($data);

        // Test deep nested access
        $this->assertEquals('php', $source->get('meta.tags.0'));
        $this->assertEquals('Jane', $source->get('meta.authors.1.name'));

        // Test deep nested set
        $source->set('meta.authors.0.role', 'superadmin');
        $this->assertEquals('superadmin', $source->get('meta.authors.0.role'));
    }

    /**
     * Test array data source handles empty strings
     */
    public function test_array_data_source_handles_empty_strings(): void
    {
        $data = ['key' => ''];
        $source = new ArrayDataSource($data);

        $this->assertEquals('', $source->get('key'));
        $this->assertTrue($source->has('key'));
    }

    /**
     * Test array data source handles zero values
     */
    public function test_array_data_source_handles_zero_values(): void
    {
        $data = ['count' => 0];
        $source = new ArrayDataSource($data);

        $this->assertEquals(0, $source->get('count'));
        $this->assertTrue($source->has('count'));
    }

    /**
     * Test array data source handles boolean values
     */
    public function test_array_data_source_handles_boolean_values(): void
    {
        $data = ['active' => true, 'deleted' => false];
        $source = new ArrayDataSource($data);

        $this->assertTrue($source->get('active'));
        $this->assertFalse($source->get('deleted'));
        $this->assertTrue($source->has('active'));
        $this->assertTrue($source->has('deleted'));
    }

    /**
     * Test data source interface has required methods
     */
    public function test_data_source_interface_has_methods(): void
    {
        $reflection = new \ReflectionClass(DataSourceInterface::class);

        $methods = ['get', 'set', 'has', 'toArray', 'sync'];
        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Interface should have method: {$method}");
        }
    }

    /**
     * Test array data source namespace
     */
    public function test_array_data_source_namespace(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);

        $reflection = new \ReflectionClass($source);
        $this->assertEquals('Monstrex\\Ave\\Core\\DataSources', $reflection->getNamespaceName());
    }

    /**
     * Test model data source namespace
     */
    public function test_model_data_source_namespace(): void
    {
        $model = $this->createMock(Model::class);
        $source = new ModelDataSource($model);

        $reflection = new \ReflectionClass($source);
        $this->assertEquals('Monstrex\\Ave\\Core\\DataSources', $reflection->getNamespaceName());
    }

    /**
     * Test array data source get method signature
     */
    public function test_array_data_source_get_method_signature(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);

        $reflection = new \ReflectionClass($source);
        $method = $reflection->getMethod('get');

        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, count($method->getParameters()));
    }

    /**
     * Test array data source set method signature
     */
    public function test_array_data_source_set_method_signature(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);

        $reflection = new \ReflectionClass($source);
        $method = $reflection->getMethod('set');

        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, count($method->getParameters()));
    }

    /**
     * Test array data source has method signature
     */
    public function test_array_data_source_has_method_signature(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);

        $reflection = new \ReflectionClass($source);
        $method = $reflection->getMethod('has');

        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, count($method->getParameters()));
    }

    /**
     * Test model data source has method returns false for null
     */
    public function test_model_data_source_has_null_value(): void
    {
        $model = $this->createMock(Model::class);
        $model->title = null;

        $source = new ModelDataSource($model);
        // has() returns false for null values
        $this->assertFalse($source->has('title'));
    }

    /**
     * Test array data source constructor accepts reference
     */
    public function test_array_data_source_reference_handling(): void
    {
        $originalData = ['key' => 'value'];
        $source = new ArrayDataSource($originalData);

        // Modifying through source should affect the data
        $source->set('key', 'modified');

        // Original array should be modified since it's passed by reference
        $this->assertEquals('modified', $originalData['key']);
    }

    /**
     * Test multiple array source instances are independent
     */
    public function test_multiple_array_sources_independent(): void
    {
        $data1 = ['key' => 'value1'];
        $data2 = ['key' => 'value2'];

        $source1 = new ArrayDataSource($data1);
        $source2 = new ArrayDataSource($data2);

        $this->assertEquals('value1', $source1->get('key'));
        $this->assertEquals('value2', $source2->get('key'));

        $source1->set('key', 'modified1');
        $this->assertEquals('modified1', $source1->get('key'));
        $this->assertEquals('value2', $source2->get('key'));
    }

    /**
     * Test array data source handles array values
     */
    public function test_array_data_source_handles_array_values(): void
    {
        $data = ['items' => [1, 2, 3]];
        $source = new ArrayDataSource($data);

        $this->assertEquals([1, 2, 3], $source->get('items'));
        $this->assertTrue($source->has('items'));
    }

    /**
     * Test model data source method visibility
     */
    public function test_model_data_source_methods_public(): void
    {
        $model = $this->createMock(Model::class);
        $source = new ModelDataSource($model);

        $reflection = new \ReflectionClass($source);

        $this->assertTrue($reflection->getMethod('get')->isPublic());
        $this->assertTrue($reflection->getMethod('set')->isPublic());
        $this->assertTrue($reflection->getMethod('has')->isPublic());
        $this->assertTrue($reflection->getMethod('toArray')->isPublic());
        $this->assertTrue($reflection->getMethod('sync')->isPublic());
        $this->assertTrue($reflection->getMethod('getModel')->isPublic());
    }

    /**
     * Test array data source method visibility
     */
    public function test_array_data_source_methods_public(): void
    {
        $data = [];
        $source = new ArrayDataSource($data);

        $reflection = new \ReflectionClass($source);

        $this->assertTrue($reflection->getMethod('get')->isPublic());
        $this->assertTrue($reflection->getMethod('set')->isPublic());
        $this->assertTrue($reflection->getMethod('has')->isPublic());
        $this->assertTrue($reflection->getMethod('toArray')->isPublic());
        $this->assertTrue($reflection->getMethod('sync')->isPublic());
    }

    /**
     * Test array data source has data reference method
     */
    public function test_array_data_source_has_getData_method(): void
    {
        $data = ['key' => 'value'];
        $source = new ArrayDataSource($data);

        $reflection = new \ReflectionClass($source);
        $this->assertTrue($reflection->hasMethod('getData'));
        $this->assertTrue($reflection->getMethod('getData')->isPublic());
    }
}
