<?php

namespace Monstrex\Ave\Admin\Resources\Menu;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\BaseAction;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

class MenuBuilderAction extends BaseAction implements RowAction
{
    protected string $key = 'menu-builder';
    protected string $color = 'info';
    protected ?string $ability = 'update';

    public function label(): string
    {
        return 'Menu Builder';
    }

    public function icon(): ?string
    {
        return 'voyager-list';
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        $model = $context->model();

        return [
            'redirect' => route('ave.resource.index', [
                'slug' => 'menu-items',
                'menu_id' => $model->getKey(),
            ]),
        ];
    }
}
