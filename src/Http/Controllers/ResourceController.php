<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Criteria\CriteriaPipeline;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Http\Controllers\Resource\Actions\IndexAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\CreateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\StoreAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\EditAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\DestroyAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\ReorderAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateGroupAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\UpdateTreeAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\InlineUpdateAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunRowAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunBulkAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunGlobalAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\RunFormAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\TableJsonAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\FormJsonAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\GetModalFormAction;
use Monstrex\Ave\Http\Controllers\Resource\Actions\GetModalFormCreateAction;
use Monstrex\Ave\Http\Controllers\Resource\Concerns\InteractsWithResources;
use Monstrex\Ave\Exceptions\ResourceException;

/**
 * Controller for managing resources (CRUD operations)
 */
class ResourceController extends Controller
{
    use InteractsWithResources;

    public function __construct(
        protected ResourceManager $resources,
        protected ResourceRenderer $renderer,
        protected FormValidator $validator,
        protected ResourcePersistence $persistence,
        protected SortableOrderService $sortingService,
        protected IndexAction $indexAction,
        protected CreateAction $createAction,
        protected StoreAction $storeAction,
        protected EditAction $editAction,
        protected UpdateAction $updateAction,
        protected DestroyAction $destroyAction,
        protected ReorderAction $reorderAction,
        protected UpdateGroupAction $updateGroupAction,
        protected UpdateTreeAction $updateTreeAction,
        protected InlineUpdateAction $inlineUpdateAction,
        protected RunRowAction $runRowAction,
        protected RunBulkAction $runBulkAction,
        protected RunGlobalAction $runGlobalAction,
        protected RunFormAction $runFormAction,
        protected TableJsonAction $tableJsonAction,
        protected FormJsonAction $formJsonAction,
        protected GetModalFormAction $getModalFormAction,
        protected GetModalFormCreateAction $getModalFormCreateAction
    ) {}

    public function runRowAction(Request $request, string $slug, string $id, string $action)
    {
        return ($this->runRowAction)($request, $slug, $id, $action);
    }

    public function runBulkAction(Request $request, string $slug, string $action)
    {
        return ($this->runBulkAction)($request, $slug, $action);
    }

    public function runGlobalAction(Request $request, string $slug, string $action)
    {
        return ($this->runGlobalAction)($request, $slug, $action);
    }

    public function runFormAction(Request $request, string $slug, string $action, ?string $id = null)
    {
        return ($this->runFormAction)($request, $slug, $action, $id);
    }

    /**
     * Format validation errors for toast notification
     *
     * @param array $errors
     * @return string
     */
    public function index(Request $request, string $slug)
    {
        return ($this->indexAction)($request, $slug);
    }

    /**
     * Show form for creating new resource
     *
     * GET /admin/resource/{slug}/create
     */
    public function create(Request $request, string $slug)
    {
        return ($this->createAction)($request, $slug);
    }

    public function store(Request $request, string $slug)
    {
        return ($this->storeAction)($request, $slug);
    }

    public function edit(Request $request, string $slug, string $id)
    {
        return ($this->editAction)($request, $slug, $id);
    }

    public function update(Request $request, string $slug, string $id)
    {
        return ($this->updateAction)($request, $slug, $id);
    }

    public function destroy(Request $request, string $slug, string $id)
    {
        return ($this->destroyAction)($request, $slug, $id);
    }

    public function inlineUpdate(Request $request, string $slug, string $id)
    {
        return ($this->inlineUpdateAction)($request, $slug, $id);
    }

    public function tableJson(Request $request, string $slug)
    {
        return ($this->tableJsonAction)($request, $slug);
    }

    /**
     * Return form schema as JSON (for SPA/AJAX)
     *
     * GET /admin/resource/{slug}/form.json
     */
    public function formJson(Request $request, string $slug)
    {
        return ($this->formJsonAction)($request, $slug);
    }


    /**
     * Update order for sortable list
     *
     * POST /admin/resource/{slug}/reorder
     */
    public function reorder(Request $request, string $slug)
    {
        return ($this->reorderAction)($request, $slug);
    }

    public function updateGroup(Request $request, string $slug)
    {
        return ($this->updateGroupAction)($request, $slug);
    }

    public function updateTree(Request $request, string $slug)
    {
        return ($this->updateTreeAction)($request, $slug);
    }

    public function getModalForm(Request $request, string $slug, string $id)
    {
        return ($this->getModalFormAction)($request, $slug, $id);
    }

    /**
     * Get modal form HTML for creating a new record
     *
     * GET /admin/resource/{slug}/modal-form-create
     */
    public function getModalFormCreate(Request $request, string $slug)
    {
        return ($this->getModalFormCreateAction)($request, $slug);
    }

}
