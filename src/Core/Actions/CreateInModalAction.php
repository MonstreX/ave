<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\GlobalAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

/**
 * Create action that opens form in modal popup instead of full page
 */
class CreateInModalAction extends BaseAction implements GlobalAction
{
    protected string $key = 'create-modal';
    protected string $label = 'Create';
    protected ?string $icon = 'voyager-plus';
    protected string $color = 'success';
    protected ?string $ability = 'create';

    public function handle(ActionContext $context, Request $request): mixed
    {
        $resourceClass = $context->resourceClass();
        $slug = $resourceClass::getSlug();

        // Get context params from request (passed from frontend via executeAction)
        // These are current page URL query params like menu_id, parent_id, etc.
        $contextParams = $request->except(['_token', '_action']);

        // Return special response that tells frontend to open modal
        return [
            'modal_form' => true,
            'fetch_url' => route('ave.resource.modal-form-create', [
                'slug' => $slug,
            ]) . '?' . http_build_query($contextParams),
            'save_url' => route('ave.resource.store', [
                'slug' => $slug,
            ]),
            'title' => 'Create ' . $resourceClass::getSingularLabel(),
            'size' => 'large',
        ];
    }
}
