<?php

namespace Monstrex\Ave\Tests\Fixtures\Actions;

use Monstrex\Ave\Core\Actions\Contracts\BulkAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Illuminate\Http\Request;

class TestBulkAction implements BulkAction
{
    public function key(): string
    {
        return 'test-bulk-action';
    }

    public function label(): string
    {
        return 'Test Bulk Action';
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        return ['processed' => count($context->models())];
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
