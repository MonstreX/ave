<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

class RestoreAction extends BaseAction implements RowAction, BulkAction
{
    protected string $color = 'warning';
    protected ?string $ability = 'delete';

    public function label(): string
    {
        return __('ave::actions.restore');
    }

    public function confirm(): ?string
    {
        return __('ave::actions.restore_confirm');
    }

    public function authorize(ActionContext $context): bool
    {
        $model = $context->model() ?? $context->models()?->first();

        return $model && in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        if ($context->mode() === 'row' && $context->model()) {
            $context->model()->restore();
            return true;
        }

        if ($context->mode() === 'bulk' && $context->models()) {
            foreach ($context->models() as $model) {
                $model->restore();
            }
            return $context->models()->count();
        }

        return null;
    }
}
