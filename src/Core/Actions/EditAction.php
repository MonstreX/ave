<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

class EditAction extends BaseAction implements RowAction
{
    protected string $key = 'edit';
    protected string $color = 'primary';
    protected ?string $ability = 'update';

    public function handle(ActionContext $context, Request $request): mixed
    {
        $resourceClass = $context->resourceClass();
        $slug = $resourceClass::getSlug();
        $model = $context->model();

        return [
            'redirect' => route('ave.resource.edit', [
                'slug' => $slug,
                'id' => $model?->getKey(),
            ]),
        ];
    }
}
