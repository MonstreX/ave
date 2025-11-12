<?php

namespace Tests\Unit\Core\Actions;

use Tests\TestCase;
use Monstrex\Ave\Tests\Fixtures\Actions\TestRowAction;
use Monstrex\Ave\Tests\Fixtures\Actions\TestBulkAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ActionsTest extends TestCase
{
    /** @test */
    public function row_action_has_correct_metadata()
    {
        $action = new TestRowAction();

        $this->assertEquals('test-row-action', $action->key());
        $this->assertEquals('Test Action', $action->label());
        $this->assertEquals('update', $action->ability());
    }

    /** @test */
    public function row_action_can_be_authorized()
    {
        $action = new TestRowAction();
        $model = $this->createMock(Model::class);
        $user = $this->createMock(\Illuminate\Contracts\Auth\Authenticatable::class);

        $context = ActionContext::row(
            TestResource::class,
            $user,
            $model
        );

        $this->assertTrue($action->authorize($context));
    }

    /** @test */
    public function row_action_can_handle_request()
    {
        $action = new TestRowAction();
        $model = $this->createMock(Model::class);
        $model->method('getKey')->willReturn(123);

        $user = $this->createMock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $request = Request::create('/', 'POST');

        $context = ActionContext::row(
            TestResource::class,
            $user,
            $model
        );

        $result = $action->handle($context, $request);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(123, $result['model_id']);
    }

    /** @test */
    public function row_action_has_empty_rules_by_default()
    {
        $action = new TestRowAction();

        $this->assertIsArray($action->rules());
        $this->assertEmpty($action->rules());
    }

    /** @test */
    public function bulk_action_has_correct_metadata()
    {
        $action = new TestBulkAction();

        $this->assertEquals('test-bulk-action', $action->key());
        $this->assertEquals('Test Bulk Action', $action->label());
        $this->assertEquals('update', $action->ability());
    }

    /** @test */
    public function bulk_action_can_handle_multiple_models()
    {
        $action = new TestBulkAction();

        $model1 = $this->createMock(Model::class);
        $model2 = $this->createMock(Model::class);
        $model3 = $this->createMock(Model::class);

        $models = new \Illuminate\Database\Eloquent\Collection([$model1, $model2, $model3]);
        $user = $this->createMock(\Illuminate\Contracts\Auth\Authenticatable::class);
        $request = Request::create('/', 'POST');

        $context = ActionContext::bulk(
            TestResource::class,
            $user,
            $models,
            [1, 2, 3]
        );

        $result = $action->handle($context, $request);

        $this->assertIsArray($result);
        $this->assertEquals(3, $result['processed']);
    }

    /** @test */
    public function resource_returns_array_of_row_actions()
    {
        $rowActions = TestResourceWithActions::rowActions();

        // Should return array of actions
        $this->assertIsArray($rowActions);
        $this->assertNotEmpty($rowActions);
    }

    /** @test */
    public function resource_returns_array_of_bulk_actions()
    {
        $bulkActions = TestResourceWithActions::bulkActions();

        $this->assertIsArray($bulkActions);
        $this->assertNotEmpty($bulkActions);
    }

    /** @test */
    public function resource_can_define_custom_actions()
    {
        $customActions = TestResourceWithActions::actions();

        $this->assertIsArray($customActions);
        $this->assertCount(2, $customActions);
    }
}

/**
 * Test Resource class for actions testing
 */
class TestResource extends Resource
{
    public static ?string $model = null;
}

/**
 * Test Resource with custom actions
 */
class TestResourceWithActions extends Resource
{
    public static ?string $model = null;

    public static function actions(): array
    {
        return [
            TestRowAction::class,
            TestBulkAction::class,
        ];
    }
}
