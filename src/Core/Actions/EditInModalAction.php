<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

/**
 * Edit action that opens form in modal popup instead of full page
 */
class EditInModalAction extends BaseAction implements RowAction
{
    protected string $key = 'edit-modal';
    protected string $label = 'Edit';
    protected string $icon = 'voyager-edit';
    protected string $color = 'primary';
    protected ?string $ability = 'update';

    public function handle(ActionContext $context, Request $request): mixed
    {
        $resourceClass = $context->resourceClass();
        $slug = $resourceClass::getSlug();
        $model = $context->model();
        $modelId = $model?->getKey();

        // Return special response that tells frontend to open modal
        return [
            'modal_form' => true,
            'fetch_url' => route('ave.resource.modal-form', [
                'slug' => $slug,
                'id' => $modelId,
            ]),
            'save_url' => route('ave.resource.update', [
                'slug' => $slug,
                'id' => $modelId,
            ]),
            'title' => 'Edit ' . $resourceClass::getSingularLabel(),
            'size' => 'large',
        ];
    }
}
