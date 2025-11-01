<?php

namespace Monstrex\Ave\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\DataSources\ModelDataSource;
use PHPUnit\Framework\TestCase;

class DataSourceTest extends TestCase
{
    /**
     * Test ModelDataSource with simple attribute access
     */
    public function test_model_data_source_get_simple_attribute(): void
    {
        $model = $this->createMockModel(['title' => 'Test Title', 'description' => 'Test Desc']);

        $dataSource = new ModelDataSource($model);

        $this->assertEquals('Test Title', $dataSource->get('title'));
        $this->assertEquals('Test Desc', $dataSource->get('description'));
    }

    /**
     * Test ModelDataSource set method
     */
    public function test_model_data_source_set_attribute(): void
    {
        $model = $this->createMockModel(['title' => 'Original']);

        $dataSource = new ModelDataSource($model);
        $dataSource->set('title', 'Updated');

        $this->assertEquals('Updated', $model->title);
    }

    /**
     * Test ModelDataSource has method
     */
    public function test_model_data_source_has_attribute(): void
    {
        $model = $this->createMockModel(['title' => 'Test', 'empty' => null]);

        $dataSource = new ModelDataSource($model);

        $this->assertTrue($dataSource->has('title'));
        $this->assertFalse($dataSource->has('empty'));
        $this->assertFalse($dataSource->has('nonexistent'));
    }

    /**
     * Test ArrayDataSource with nested dot notation
     */
    public function test_array_data_source_get_nested(): void
    {
        $data = [
            'title' => 'Test',
            'items' => [
                ['name' => 'Item 1', 'value' => 100],
                ['name' => 'Item 2', 'value' => 200],
            ],
        ];

        $dataSource = new ArrayDataSource($data);

        $this->assertEquals('Test', $dataSource->get('title'));
        $this->assertEquals('Item 1', $dataSource->get('items.0.name'));
        $this->assertEquals(200, $dataSource->get('items.1.value'));
    }

    /**
     * Test ArrayDataSource set with dot notation
     */
    public function test_array_data_source_set_nested(): void
    {
        $data = [
            'items' => [
                ['name' => 'Item 1'],
            ],
        ];

        $dataSource = new ArrayDataSource($data);
        $dataSource->set('items.0.name', 'Updated Item 1');

        $this->assertEquals('Updated Item 1', $data['items'][0]['name']);
    }

    /**
     * Test ArrayDataSource creates nested structure
     */
    public function test_array_data_source_creates_nested_structure(): void
    {
        $data = [];

        $dataSource = new ArrayDataSource($data);
        $dataSource->set('profile.user.name', 'John Doe');

        $this->assertEquals('John Doe', $data['profile']['user']['name']);
        $this->assertEquals('John Doe', $dataSource->get('profile.user.name'));
    }

    /**
     * Test ArrayDataSource has with dot notation
     */
    public function test_array_data_source_has_nested(): void
    {
        $data = [
            'profile' => [
                'name' => 'John',
            ],
        ];

        $dataSource = new ArrayDataSource($data);

        $this->assertTrue($dataSource->has('profile.name'));
        $this->assertFalse($dataSource->has('profile.email'));
        $this->assertFalse($dataSource->has('nonexistent.key'));
    }

    /**
     * Test ArrayDataSource reference behavior
     */
    public function test_array_data_source_modifies_original_array(): void
    {
        $data = ['field' => 'value'];
        $dataSource = new ArrayDataSource($data);

        $dataSource->set('field', 'new value');

        // Original array should be modified
        $this->assertEquals('new value', $data['field']);
    }

    /**
     * Test toArray method
     */
    public function test_to_array_method(): void
    {
        $model = $this->createMockModel(['title' => 'Test', 'id' => 1]);
        $modelDataSource = new ModelDataSource($model);

        $expected = ['title' => 'Test', 'id' => 1];
        $this->assertEquals($expected, $modelDataSource->toArray());

        $arrayData = ['title' => 'Test', 'id' => 1];
        $arrayDataSource = new ArrayDataSource($arrayData);

        $this->assertEquals($expected, $arrayDataSource->toArray());
    }

    /**
     * Helper: Create a mock model with attributes
     */
    private function createMockModel(array $attributes = []): Model
    {
        $model = new class extends Model {
            protected $fillable = ['id', 'title', 'description', 'empty'];
        };

        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }
}
