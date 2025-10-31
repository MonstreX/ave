<?php

namespace Monstrex\Ave\Tests\Unit\Phase3;

use PHPUnit\Framework\TestCase;
use Monstrex\Ave\Core\Actions\Action;
use Monstrex\Ave\Core\Actions\BulkAction;

class ActionTest extends TestCase
{
    public function test_action_creation()
    {
        $action = Action::make('edit')
            ->label('Edit')
            ->icon('pencil')
            ->color('blue');

        $this->assertEquals('edit', $action->key());
    }

    public function test_action_handle()
    {
        $action = Action::make('activate')
            ->handle(function($record) {
                return $record->id . ' activated';
            });

        $mock = (object)['id' => 123];
        $result = $action->execute($mock);

        $this->assertEquals('123 activated', $result);
    }

    public function test_action_confirmation()
    {
        $action = Action::make('delete')
            ->requiresConfirmation(true, 'Are you sure?');

        $array = $action->toArray();
        $this->assertTrue($array['requiresConfirmation']);
        $this->assertEquals('Are you sure?', $array['confirmMessage']);
    }

    public function test_bulk_action_creation()
    {
        $bulkAction = BulkAction::make('publish')
            ->label('Publish Selected')
            ->icon('check')
            ->color('green');

        $this->assertEquals('publish', $bulkAction->key());
    }

    public function test_bulk_action_execute()
    {
        $bulkAction = BulkAction::make('delete')
            ->handle(function($ids) {
                return count($ids);
            });

        $result = $bulkAction->execute([1, 2, 3]);
        $this->assertEquals(3, $result);
    }
}
