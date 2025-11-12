<?php

namespace Monstrex\Ave\Tests\Fixtures\Actions;

use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Illuminate\Http\Request;

class TestRowAction implements RowAction
{
    public function key(): string
    {
        return 'test-row-action';
    }

    public function label(): string
    {
        return 'Test Action';
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        return ['success' => true, 'model_id' => $context->model()->getKey()];
    }

    public function authorize(ActionContext $context): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function ability(): ?string
    {
        return 'update';
    }
}
