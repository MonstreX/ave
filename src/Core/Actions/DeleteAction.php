<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

class DeleteAction extends BaseAction implements RowAction, BulkAction
{
    protected string $color = 'danger';
    protected ?string $ability = 'delete';

    public function label(): string
    {
        return __('ave::actions.delete');
    }

    public function confirm(): ?string
    {
        return __('ave::actions.delete_confirm');
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        if ($context->mode() === 'row' && $context->model()) {
            $context->model()->delete();
            return true;
        }

        if ($context->mode() === 'bulk' && $context->models()) {
            foreach ($context->models() as $model) {
                $model->delete();
            }
            return $context->models()->count();
        }

        return null;
    }
}
