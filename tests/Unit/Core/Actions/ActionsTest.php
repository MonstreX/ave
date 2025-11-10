<?php

namespace Monstrex\Ave\Tests\Unit\Core\Actions;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Actions\Action;
use Monstrex\Ave\Core\Actions\BulkAction;
use Illuminate\Http\Request;

/**
 * ActionsTest - Unit tests for Action and BulkAction classes.
 *
 * Tests the action system which provides:
 * - Single record actions with callbacks
 * - Bulk record actions with reflection-based parameter handling
 * - Fluent interface for action configuration
 * - Confirmation dialogs
 * - Icon, color, and label customization
 * - Array serialization for API responses
 */
class ActionsTest extends TestCase
{
    /**
     * Test action can be instantiated
     */
    public function test_action_can_be_instantiated(): void
    {
        $action = new Action('edit');
        $this->assertInstanceOf(Action::class, $action);
    }

    /**
     * Test action make factory method
     */
    public function test_action_make_factory(): void
    {
        $action = Action::make('delete');
        $this->assertInstanceOf(Action::class, $action);
        $this->assertEquals('delete', $action->key());
    }

    /**
     * Test action label method is fluent
     */
    public function test_action_label_is_fluent(): void
    {
        $action = new Action('edit');
        $result = $action->label('Edit Item');

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test action icon method is fluent
     */
    public function test_action_icon_is_fluent(): void
    {
        $action = new Action('edit');
        $result = $action->icon('fa fa-edit');

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test action color method is fluent
     */
    public function test_action_color_is_fluent(): void
    {
        $action = new Action('delete');
        $result = $action->color('danger');

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test action url method is fluent
     */
    public function test_action_url_is_fluent(): void
    {
        $action = new Action('view');
        $result = $action->url('/items/{id}');

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test action handle method is fluent
     */
    public function test_action_handle_is_fluent(): void
    {
        $action = new Action('publish');
        $result = $action->handle(fn() => null);

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test action requires confirmation is fluent
     */
    public function test_action_requires_confirmation_is_fluent(): void
    {
        $action = new Action('delete');
        $result = $action->requiresConfirmation();

        $this->assertInstanceOf(Action::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test action fluent interface chaining
     */
    public function test_action_fluent_chaining(): void
    {
        $action = Action::make('delete')
            ->label('Delete Item')
            ->icon('fa fa-trash')
            ->color('danger')
            ->url('/items/{id}/delete')
            ->requiresConfirmation(true, 'Are you sure?');

        $this->assertEquals('delete', $action->key());
    }

    /**
     * Test action execute with callback
     */
    public function test_action_execute_with_callback(): void
    {
        $executed = false;
        $action = Action::make('test')
            ->handle(function($record) use (&$executed) {
                $executed = true;
                return $record;
            });

        $result = $action->execute(['id' => 1]);
        $this->assertTrue($executed);
        $this->assertEquals(['id' => 1], $result);
    }

    /**
     * Test action execute without callback
     */
    public function test_action_execute_without_callback(): void
    {
        $action = new Action('test');
        $result = $action->execute(['id' => 1]);
        $this->assertNull($result);
    }

    /**
     * Test action toArray method
     */
    public function test_action_to_array_method(): void
    {
        $action = Action::make('edit')
            ->label('Edit')
            ->icon('fa fa-edit')
            ->color('primary')
            ->url('/edit')
            ->requiresConfirmation(true, 'Confirm?');

        $array = $action->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('edit', $array['key']);
        $this->assertEquals('Edit', $array['label']);
        $this->assertEquals('fa fa-edit', $array['icon']);
        $this->assertEquals('primary', $array['color']);
        $this->assertEquals('/edit', $array['url']);
        $this->assertTrue($array['requiresConfirmation']);
        $this->assertEquals('Confirm?', $array['confirmMessage']);
    }

    /**
     * Test action toArray default label
     */
    public function test_action_to_array_default_label(): void
    {
        $action = new Action('publish');
        $array = $action->toArray();

        $this->assertEquals('Publish', $array['label']);
    }

    /**
     * Test multiple action instances
     */
    public function test_multiple_action_instances(): void
    {
        $action1 = Action::make('edit');
        $action2 = Action::make('delete');

        $this->assertNotSame($action1, $action2);
        $this->assertEquals('edit', $action1->key());
        $this->assertEquals('delete', $action2->key());
    }

    /**
     * Test bulk action can be instantiated
     */
    public function test_bulk_action_can_be_instantiated(): void
    {
        $action = new BulkAction('publish');
        $this->assertInstanceOf(BulkAction::class, $action);
    }

    /**
     * Test bulk action make factory
     */
    public function test_bulk_action_make_factory(): void
    {
        $action = BulkAction::make('archive');
        $this->assertInstanceOf(BulkAction::class, $action);
        $this->assertEquals('archive', $action->key());
    }

    /**
     * Test bulk action label method is fluent
     */
    public function test_bulk_action_label_is_fluent(): void
    {
        $action = new BulkAction('delete');
        $result = $action->label('Delete All');

        $this->assertInstanceOf(BulkAction::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test bulk action icon method is fluent
     */
    public function test_bulk_action_icon_is_fluent(): void
    {
        $action = new BulkAction('delete');
        $result = $action->icon('fa fa-trash');

        $this->assertInstanceOf(BulkAction::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test bulk action color method is fluent
     */
    public function test_bulk_action_color_is_fluent(): void
    {
        $action = new BulkAction('delete');
        $result = $action->color('danger');

        $this->assertInstanceOf(BulkAction::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test bulk action handle method is fluent
     */
    public function test_bulk_action_handle_is_fluent(): void
    {
        $action = new BulkAction('publish');
        $result = $action->handle(fn() => null);

        $this->assertInstanceOf(BulkAction::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test bulk action requires confirmation is fluent
     */
    public function test_bulk_action_requires_confirmation_is_fluent(): void
    {
        $action = new BulkAction('delete');
        $result = $action->requiresConfirmation();

        $this->assertInstanceOf(BulkAction::class, $result);
        $this->assertSame($action, $result);
    }

    /**
     * Test bulk action fluent chaining
     */
    public function test_bulk_action_fluent_chaining(): void
    {
        $action = BulkAction::make('delete')
            ->label('Delete All')
            ->icon('fa fa-trash')
            ->color('danger')
            ->requiresConfirmation(true, 'Delete all?');

        $this->assertEquals('delete', $action->key());
    }

    /**
     * Test bulk action execute with zero parameters
     */
    public function test_bulk_action_execute_zero_params(): void
    {
        $executed = false;
        $action = BulkAction::make('test')
            ->handle(function() use (&$executed) {
                $executed = true;
                return 'done';
            });

        $result = $action->execute([], $this->createMock(Request::class));
        $this->assertTrue($executed);
        $this->assertEquals('done', $result);
    }

    /**
     * Test bulk action execute with one parameter
     */
    public function test_bulk_action_execute_one_param(): void
    {
        $records = [['id' => 1], ['id' => 2]];
        $action = BulkAction::make('test')
            ->handle(function($recordsArg) {
                return count($recordsArg);
            });

        $result = $action->execute($records, $this->createMock(Request::class));
        $this->assertEquals(2, $result);
    }

    /**
     * Test bulk action execute with two parameters
     */
    public function test_bulk_action_execute_two_params(): void
    {
        $records = [['id' => 1]];
        $request = $this->createMock(Request::class);
        $request->id = 123;

        $action = BulkAction::make('test')
            ->handle(function($recordsArg, $requestArg) {
                return $requestArg->id;
            });

        $result = $action->execute($records, $request);
        $this->assertEquals(123, $result);
    }

    /**
     * Test bulk action execute without callback
     */
    public function test_bulk_action_execute_without_callback(): void
    {
        $action = new BulkAction('test');
        $result = $action->execute([], $this->createMock(Request::class));
        $this->assertNull($result);
    }

    /**
     * Test bulk action toArray method
     */
    public function test_bulk_action_to_array_method(): void
    {
        $action = BulkAction::make('publish')
            ->label('Publish All')
            ->icon('fa fa-check')
            ->color('success')
            ->requiresConfirmation(true, 'Publish?');

        $array = $action->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('publish', $array['key']);
        $this->assertEquals('Publish All', $array['label']);
        $this->assertEquals('fa fa-check', $array['icon']);
        $this->assertEquals('success', $array['color']);
        $this->assertTrue($array['requiresConfirmation']);
        $this->assertEquals('Publish?', $array['confirmMessage']);
    }

    /**
     * Test bulk action toArray default label
     */
    public function test_bulk_action_to_array_default_label(): void
    {
        $action = new BulkAction('archive');
        $array = $action->toArray();

        $this->assertEquals('Archive', $array['label']);
    }

    /**
     * Test multiple bulk action instances
     */
    public function test_multiple_bulk_action_instances(): void
    {
        $action1 = BulkAction::make('publish');
        $action2 = BulkAction::make('archive');

        $this->assertNotSame($action1, $action2);
        $this->assertEquals('publish', $action1->key());
        $this->assertEquals('archive', $action2->key());
    }

    /**
     * Test action with special characters in label
     */
    public function test_action_special_characters_label(): void
    {
        $action = Action::make('test')
            ->label('Edit & Save');

        $array = $action->toArray();
        $this->assertEquals('Edit & Save', $array['label']);
    }

    /**
     * Test bulk action with special characters in label
     */
    public function test_bulk_action_special_characters_label(): void
    {
        $action = BulkAction::make('test')
            ->label('Delete & Archive');

        $array = $action->toArray();
        $this->assertEquals('Delete & Archive', $array['label']);
    }

    /**
     * Test action method visibility
     */
    public function test_action_methods_public(): void
    {
        $reflection = new \ReflectionClass(Action::class);

        $publicMethods = [
            'make',
            'label',
            'icon',
            'color',
            'url',
            'handle',
            'requiresConfirmation',
            'key',
            'execute',
            'toArray'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Action should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test bulk action method visibility
     */
    public function test_bulk_action_methods_public(): void
    {
        $reflection = new \ReflectionClass(BulkAction::class);

        $publicMethods = [
            'make',
            'label',
            'icon',
            'color',
            'handle',
            'requiresConfirmation',
            'key',
            'execute',
            'toArray'
        ];

        foreach ($publicMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "BulkAction should have method: {$method}"
            );
            $this->assertTrue(
                $reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test action namespace
     */
    public function test_action_namespace(): void
    {
        $reflection = new \ReflectionClass(Action::class);
        $this->assertEquals('Monstrex\\Ave\\Core\\Actions', $reflection->getNamespaceName());
    }

    /**
     * Test bulk action namespace
     */
    public function test_bulk_action_namespace(): void
    {
        $reflection = new \ReflectionClass(BulkAction::class);
        $this->assertEquals('Monstrex\\Ave\\Core\\Actions', $reflection->getNamespaceName());
    }
}
