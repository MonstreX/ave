<?php

namespace Monstrex\Ave\Tests\Unit\Core;

use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\Actions\BaseAction;
use Monstrex\Ave\Core\Actions\Contracts\FormAction as FormActionContract;
use Monstrex\Ave\Core\Actions\Contracts\GlobalAction as GlobalActionContract;
use Monstrex\Ave\Core\Actions\Contracts\RowAction as RowActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Core\Resource;
use PHPUnit\Framework\TestCase;

class ResourceActionsTest extends TestCase
{
    public function test_row_actions_include_defaults(): void
    {
        $actions = SampleResource::rowActions();
        $keys = array_map(fn ($action) => $action->key(), $actions);

        $this->assertContains('edit', $keys);
        $this->assertContains('delete', $keys);
        $this->assertContains('sample-row', $keys);
    }

    public function test_bulk_actions_include_default_delete(): void
    {
        $actions = SampleResource::bulkActions();
        $keys = array_map(fn ($action) => $action->key(), $actions);

        $this->assertContains('delete', $keys);
    }

    public function test_find_action_by_key(): void
    {
        $action = SampleResource::findAction('sample-row', RowActionContract::class);
        $this->assertInstanceOf(SampleRowAction::class, $action);

        $edit = SampleResource::findAction('edit', RowActionContract::class);
        $this->assertNotNull($edit);
    }

    public function test_form_and_global_actions_resolved(): void
    {
        $formActions = SampleResource::formActions();
        $this->assertGreaterThanOrEqual(3, count($formActions));
        $keys = array_map(fn ($action) => $action->key(), $formActions);
        $this->assertContains('sample-form', $keys);

        $globalActions = SampleResource::globalActions();
        $this->assertCount(1, $globalActions);
        $this->assertInstanceOf(SampleGlobalAction::class, $globalActions[0]);
    }
}

class SampleActionModel extends Model
{
    protected $table = 'sample_actions';
}

class SampleResource extends Resource
{
    public static ?string $model = SampleActionModel::class;

    public static function actions(): array
    {
        return [
            SampleRowAction::class,
            SampleFormAction::class,
            SampleGlobalAction::class,
        ];
    }
}

class SampleRowAction extends BaseAction implements RowActionContract
{
    protected string $key = 'sample-row';

    public function handle(ActionContext $context, \Illuminate\Http\Request $request): mixed
    {
        return true;
    }
}

class SampleFormAction extends BaseAction implements FormActionContract
{
    protected string $key = 'sample-form';

    public function handle(ActionContext $context, \Illuminate\Http\Request $request): mixed
    {
        return true;
    }
}

class SampleGlobalAction extends BaseAction implements GlobalActionContract
{
    protected string $key = 'sample-global';

    public function handle(ActionContext $context, \Illuminate\Http\Request $request): mixed
    {
        return true;
    }
}
