<?php

namespace Monstrex\Ave\Tests\Unit\Core\Filters;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Filters\Filter;
use Monstrex\Ave\Core\Filters\SelectFilter;
use Monstrex\Ave\Core\Filters\DateFilter;

/**
 * FilterTest - Unit tests for Filter classes.
 *
 * Tests the Filter hierarchy which includes:
 * - Base Filter abstract class with fluent interface
 * - SelectFilter for single/multiple select filtering
 * - DateFilter for date range and comparison filtering
 * - Query builder integration
 * - Array serialization for API responses
 */
class FilterTest extends TestCase
{
    private SelectFilter $selectFilter;
    private DateFilter $dateFilter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selectFilter = new SelectFilter('status');
        $this->dateFilter = new DateFilter('created_at');
    }

    /**
     * Test filter can be instantiated via make factory
     */
    public function test_select_filter_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SelectFilter::class, $this->selectFilter);
    }

    /**
     * Test date filter can be instantiated via make factory
     */
    public function test_date_filter_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DateFilter::class, $this->dateFilter);
    }

    /**
     * Test filter make static factory method
     */
    public function test_filter_make_static_factory(): void
    {
        $filter = SelectFilter::make('category');
        $this->assertInstanceOf(SelectFilter::class, $filter);
        $this->assertEquals('category', $filter->key());
    }

    /**
     * Test filter key method
     */
    public function test_filter_key_method(): void
    {
        $this->assertEquals('status', $this->selectFilter->key());
        $this->assertEquals('created_at', $this->dateFilter->key());
    }

    /**
     * Test filter label method is fluent
     */
    public function test_filter_label_method_is_fluent(): void
    {
        $result = $this->selectFilter->label('Status Filter');
        $this->assertInstanceOf(SelectFilter::class, $result);
        $this->assertSame($this->selectFilter, $result);
    }

    /**
     * Test filter label can be set and retrieved
     */
    public function test_filter_label_can_be_set(): void
    {
        $this->selectFilter->label('Article Status');
        $this->assertEquals('Article Status', $this->selectFilter->getLabel());
    }

    /**
     * Test filter default label generation
     */
    public function test_filter_default_label_generation(): void
    {
        $filter = SelectFilter::make('user_status');
        $this->assertEquals('User status', $filter->getLabel());
    }

    /**
     * Test filter default method is fluent
     */
    public function test_filter_default_method_is_fluent(): void
    {
        $result = $this->selectFilter->default('active');
        $this->assertInstanceOf(SelectFilter::class, $result);
        $this->assertSame($this->selectFilter, $result);
    }

    /**
     * Test filter default value can be set
     */
    public function test_filter_default_value_can_be_set(): void
    {
        $this->selectFilter->default('published');
        $this->assertEquals('published', $this->selectFilter->toArray()['default']);
    }

    /**
     * Test select filter options method is fluent
     */
    public function test_select_filter_options_method_is_fluent(): void
    {
        $result = $this->selectFilter->options([]);
        $this->assertInstanceOf(SelectFilter::class, $result);
        $this->assertSame($this->selectFilter, $result);
    }

    /**
     * Test select filter options can be set
     */
    public function test_select_filter_options_can_be_set(): void
    {
        $options = ['active' => 'Active', 'inactive' => 'Inactive'];
        $this->selectFilter->options($options);
        $this->assertEquals($options, $this->selectFilter->toArray()['options']);
    }

    /**
     * Test select filter multiple method is fluent
     */
    public function test_select_filter_multiple_method_is_fluent(): void
    {
        $result = $this->selectFilter->multiple();
        $this->assertInstanceOf(SelectFilter::class, $result);
        $this->assertSame($this->selectFilter, $result);
    }

    /**
     * Test select filter multiple can be enabled
     */
    public function test_select_filter_multiple_can_be_enabled(): void
    {
        $this->selectFilter->multiple(true);
        $this->assertTrue($this->selectFilter->toArray()['multiple']);
    }

    /**
     * Test select filter multiple can be disabled
     */
    public function test_select_filter_multiple_can_be_disabled(): void
    {
        $this->selectFilter->multiple(false);
        $this->assertFalse($this->selectFilter->toArray()['multiple']);
    }

    /**
     * Test select filter multiple defaults to false
     */
    public function test_select_filter_multiple_default(): void
    {
        $this->assertFalse($this->selectFilter->toArray()['multiple']);
    }

    /**
     * Test select filter has apply method
     */
    public function test_select_filter_has_apply_method(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $this->assertTrue($reflection->hasMethod('apply'));
        $this->assertTrue($reflection->getMethod('apply')->isPublic());
    }

    /**
     * Test select filter apply method signature
     */
    public function test_select_filter_apply_method_parameters(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $method = $reflection->getMethod('apply');
        $parameters = $method->getParameters();

        $this->assertEquals(2, count($parameters));
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('value', $parameters[1]->getName());
    }

    /**
     * Test select filter apply method return type
     */
    public function test_select_filter_apply_return_type(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $method = $reflection->getMethod('apply');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\\Database\\Eloquent\\Builder', $returnType->getName());
    }

    /**
     * Test select filter configuration
     */
    public function test_select_filter_configuration(): void
    {
        $filter = SelectFilter::make('status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->multiple(true);

        $array = $filter->toArray();
        $this->assertTrue($array['multiple']);
        $this->assertCount(2, $array['options']);
    }

    /**
     * Test select filter single select config
     */
    public function test_select_filter_single_select(): void
    {
        $filter = SelectFilter::make('category')
            ->options(['news' => 'News', 'blog' => 'Blog'])
            ->multiple(false);

        $array = $filter->toArray();
        $this->assertFalse($array['multiple']);
    }

    /**
     * Test select filter toArray method
     */
    public function test_select_filter_to_array_method(): void
    {
        $this->selectFilter
            ->label('Status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->multiple(true);

        $array = $this->selectFilter->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('status', $array['key']);
        $this->assertEquals('Status', $array['label']);
        $this->assertTrue($array['multiple']);
        $this->assertIsArray($array['options']);
    }

    /**
     * Test date filter operator method is fluent
     */
    public function test_date_filter_operator_method_is_fluent(): void
    {
        $result = $this->dateFilter->operator('>=');
        $this->assertInstanceOf(DateFilter::class, $result);
        $this->assertSame($this->dateFilter, $result);
    }

    /**
     * Test date filter operator can be set
     */
    public function test_date_filter_operator_can_be_set(): void
    {
        $this->dateFilter->operator('>=');
        $this->assertEquals('>=', $this->dateFilter->toArray()['operator']);
    }

    /**
     * Test date filter operator defaults to equals
     */
    public function test_date_filter_operator_default(): void
    {
        $this->assertEquals('=', $this->dateFilter->toArray()['operator']);
    }

    /**
     * Test date filter format method is fluent
     */
    public function test_date_filter_format_method_is_fluent(): void
    {
        $result = $this->dateFilter->format('Y-m-d H:i:s');
        $this->assertInstanceOf(DateFilter::class, $result);
        $this->assertSame($this->dateFilter, $result);
    }

    /**
     * Test date filter format can be set
     */
    public function test_date_filter_format_can_be_set(): void
    {
        $this->dateFilter->format('d/m/Y');
        $this->assertEquals('d/m/Y', $this->dateFilter->toArray()['format']);
    }

    /**
     * Test date filter format defaults to Y-m-d
     */
    public function test_date_filter_format_default(): void
    {
        $this->assertEquals('Y-m-d', $this->dateFilter->toArray()['format']);
    }

    /**
     * Test date filter has apply method
     */
    public function test_date_filter_has_apply_method(): void
    {
        $reflection = new \ReflectionClass($this->dateFilter);
        $this->assertTrue($reflection->hasMethod('apply'));
        $this->assertTrue($reflection->getMethod('apply')->isPublic());
    }

    /**
     * Test date filter apply method signature
     */
    public function test_date_filter_apply_method_parameters(): void
    {
        $reflection = new \ReflectionClass($this->dateFilter);
        $method = $reflection->getMethod('apply');
        $parameters = $method->getParameters();

        $this->assertEquals(2, count($parameters));
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('value', $parameters[1]->getName());
    }

    /**
     * Test date filter apply method return type
     */
    public function test_date_filter_apply_return_type(): void
    {
        $reflection = new \ReflectionClass($this->dateFilter);
        $method = $reflection->getMethod('apply');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\\Database\\Eloquent\\Builder', $returnType->getName());
    }

    /**
     * Test date filter with different operators
     */
    public function test_date_filter_with_different_operators(): void
    {
        $operators = ['=', '>', '<', '>=', '<=', '!='];

        foreach ($operators as $operator) {
            $filter = DateFilter::make('created_at')->operator($operator);
            $array = $filter->toArray();
            $this->assertEquals($operator, $array['operator']);
        }
    }

    /**
     * Test date filter toArray method
     */
    public function test_date_filter_to_array_method(): void
    {
        $this->dateFilter
            ->label('Created Date')
            ->operator('>=')
            ->format('Y-m-d H:i:s');

        $array = $this->dateFilter->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('created_at', $array['key']);
        $this->assertEquals('Created Date', $array['label']);
        $this->assertEquals('>=', $array['operator']);
        $this->assertEquals('Y-m-d H:i:s', $array['format']);
    }

    /**
     * Test filter fluent interface chaining for select filter
     */
    public function test_select_filter_fluent_chaining(): void
    {
        $result = SelectFilter::make('status')
            ->label('Filter by Status')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->multiple(true)
            ->default('active');

        $this->assertInstanceOf(SelectFilter::class, $result);
        $array = $result->toArray();
        $this->assertEquals('status', $array['key']);
        $this->assertTrue($array['multiple']);
        $this->assertEquals('active', $array['default']);
    }

    /**
     * Test filter fluent interface chaining for date filter
     */
    public function test_date_filter_fluent_chaining(): void
    {
        $result = DateFilter::make('created_at')
            ->label('Filter by Date')
            ->operator('>=')
            ->format('d/m/Y')
            ->default('2024-01-01');

        $this->assertInstanceOf(DateFilter::class, $result);
        $array = $result->toArray();
        $this->assertEquals('created_at', $array['key']);
        $this->assertEquals('>=', $array['operator']);
        $this->assertEquals('d/m/Y', $array['format']);
    }

    /**
     * Test multiple filter instances
     */
    public function test_multiple_filter_instances(): void
    {
        $filter1 = SelectFilter::make('status')->multiple(true);
        $filter2 = SelectFilter::make('category')->multiple(false);
        $filter3 = DateFilter::make('created_at');

        $this->assertNotSame($filter1, $filter2);
        $this->assertNotSame($filter2, $filter3);
        $this->assertTrue($filter1->toArray()['multiple']);
        $this->assertFalse($filter2->toArray()['multiple']);
    }

    /**
     * Test select filter with complex options
     */
    public function test_select_filter_with_complex_options(): void
    {
        $options = [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
            'trash' => 'Trash'
        ];

        $filter = SelectFilter::make('status')
            ->options($options)
            ->label('Article Status');

        $array = $filter->toArray();
        $this->assertEquals($options, $array['options']);
        $this->assertEquals('Article Status', $array['label']);
    }

    /**
     * Test filter method visibility
     */
    public function test_filter_methods_are_public(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);

        $publicMethods = [
            'key',
            'label',
            'default',
            'getLabel',
            'apply',
            'toArray',
            'options',
            'multiple'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "SelectFilter should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test filter has make static method
     */
    public function test_filter_has_make_static_method(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $this->assertTrue($reflection->hasMethod('make'));
        $this->assertTrue($reflection->getMethod('make')->isStatic());
    }

    /**
     * Test filter namespace
     */
    public function test_filter_namespace(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $this->assertEquals('Monstrex\\Ave\\Core\\Filters', $reflection->getNamespaceName());
    }

    /**
     * Test select filter class name
     */
    public function test_select_filter_class_name(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $this->assertEquals('SelectFilter', $reflection->getShortName());
    }

    /**
     * Test date filter class name
     */
    public function test_date_filter_class_name(): void
    {
        $reflection = new \ReflectionClass($this->dateFilter);
        $this->assertEquals('DateFilter', $reflection->getShortName());
    }

    /**
     * Test select filter extends filter
     */
    public function test_select_filter_extends_filter(): void
    {
        $reflection = new \ReflectionClass($this->selectFilter);
        $this->assertTrue($reflection->isSubclassOf(Filter::class));
    }

    /**
     * Test date filter extends filter
     */
    public function test_date_filter_extends_filter(): void
    {
        $reflection = new \ReflectionClass($this->dateFilter);
        $this->assertTrue($reflection->isSubclassOf(Filter::class));
    }

    /**
     * Test select filter empty options
     */
    public function test_select_filter_empty_options(): void
    {
        $filter = SelectFilter::make('status');
        $array = $filter->toArray();
        $this->assertEmpty($array['options']);
    }

    /**
     * Test date filter with label override
     */
    public function test_date_filter_with_label_override(): void
    {
        $filter = DateFilter::make('published_at')
            ->label('Custom Published Date Label');

        $this->assertEquals('Custom Published Date Label', $filter->getLabel());
    }

    /**
     * Test select filter with label override
     */
    public function test_select_filter_with_label_override(): void
    {
        $filter = SelectFilter::make('status_type')
            ->label('Custom Status Label');

        $this->assertEquals('Custom Status Label', $filter->getLabel());
    }

    /**
     * Test date filter configuration snapshot
     */
    public function test_date_filter_configuration_snapshot(): void
    {
        $filter = DateFilter::make('last_login')
            ->label('Last Login Date')
            ->operator('>=')
            ->format('Y-m-d')
            ->default('2024-01-01');

        $array = $filter->toArray();

        $this->assertEquals([
            'key' => 'last_login',
            'label' => 'Last Login Date',
            'default' => '2024-01-01',
            'operator' => '>=',
            'format' => 'Y-m-d'
        ], $array);
    }

    /**
     * Test select filter configuration snapshot
     */
    public function test_select_filter_configuration_snapshot(): void
    {
        $options = ['admin' => 'Administrator', 'user' => 'User'];
        $filter = SelectFilter::make('role')
            ->label('User Role')
            ->options($options)
            ->multiple(false)
            ->default('user');

        $array = $filter->toArray();

        $this->assertEquals([
            'key' => 'role',
            'label' => 'User Role',
            'default' => 'user',
            'options' => $options,
            'multiple' => false
        ], $array);
    }

    /**
     * Test filter base class is abstract
     */
    public function test_filter_base_class_is_abstract(): void
    {
        $reflection = new \ReflectionClass(Filter::class);
        $this->assertTrue($reflection->isAbstract());
    }
}
