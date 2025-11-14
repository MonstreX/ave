<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Criteria\CriteriaPipeline;
use Monstrex\Ave\Core\Form;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Core\Sorting\SortableOrderService;
use Monstrex\Ave\Support\Http\RequestDebugSanitizer;
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
use Monstrex\Ave\Http\Controllers\Resource\Concerns\InteractsWithResources;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Core\Actions\Contracts\ActionInterface;
use Monstrex\Ave\Core\Actions\Contracts\RowAction as RowActionContract;
use Monstrex\Ave\Core\Actions\Contracts\BulkAction as BulkActionContract;
use Monstrex\Ave\Core\Actions\Contracts\FormAction as FormActionContract;
use Monstrex\Ave\Core\Actions\Contracts\GlobalAction as GlobalActionContract;
use Monstrex\Ave\Core\Actions\Support\ActionContext;

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
        protected RequestDebugSanitizer $requestSanitizer,
        protected IndexAction $indexAction,
        protected CreateAction $createAction,
        protected StoreAction $storeAction,
        protected EditAction $editAction,
        protected UpdateAction $updateAction,
        protected DestroyAction $destroyAction,
        protected ReorderAction $reorderAction,
        protected UpdateGroupAction $updateGroupAction,
        protected UpdateTreeAction $updateTreeAction,
        protected InlineUpdateAction $inlineUpdateAction
    ) {}

    public function runRowAction(Request $request, string $slug, string $id, string $action)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'view', $request);

        $actionInstance = $resourceClass::findAction($action, RowActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $model = $this->findModelOrFail($resourceClass, $slug, $id);
        $ability = $actionInstance->ability() ?? 'update';

        if (!$resource->can($ability, $request->user(), $model)) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::row($resourceClass, $request->user(), $model);
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);

        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }

    public function runBulkAction(Request $request, string $slug, string $action)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $actionInstance = $resourceClass::findAction($action, BulkActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $requestedIds = collect($validated['ids'])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values();

        $keyName = $modelClass::make()->getKeyName();
        $models = $modelClass::whereIn($keyName, $requestedIds->all())->get();

        if ($models->isEmpty()) {
            throw ResourceException::modelNotFound($slug, implode(',', $requestedIds->all()));
        }

        $requestedStringIds = $requestedIds->map(fn ($id) => (string) $id);
        $foundStringIds = $models->pluck($keyName)->map(fn ($id) => (string) $id);
        $missing = $requestedStringIds->diff($foundStringIds);

        if ($missing->isNotEmpty()) {
            throw ResourceException::modelNotFound($slug, implode(',', $missing->all()));
        }

        $ability = $actionInstance->ability() ?? 'update';

        $unauthorizedModels = $models->filter(
            fn ($model) => !$resource->can($ability, $request->user(), $model)
        );

        if ($unauthorizedModels->isNotEmpty()) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::bulk($resourceClass, $request->user(), $models, $requestedIds->all());
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);
        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }

    public function runGlobalAction(Request $request, string $slug, string $action)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $actionInstance = $resourceClass::findAction($action, GlobalActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $ability = $actionInstance->ability() ?? 'viewAny';
        if (!$resource->can($ability, $request->user())) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::global($resourceClass, $request->user());
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);
        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
    }

    public function runFormAction(Request $request, string $slug, string $action, ?string $id = null)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, $id ? 'update' : 'create', $request);

        $actionInstance = $resourceClass::findAction($action, FormActionContract::class);
        if (!$actionInstance) {
            return $this->actionNotFoundResponse($request, $action);
        }

        $model = $id
            ? $this->findModelOrFail($resourceClass, $slug, $id)
            : (new ($resourceClass::$model)());

        $ability = $actionInstance->ability() ?? ($id ? 'update' : 'create');

        if (!$resource->can($ability, $request->user(), $id ? $model : null)) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        $context = ActionContext::form($resourceClass, $request->user(), $model);
        $this->authorizeAction($actionInstance, $context, $slug);
        $this->validateActionRequest($request, $actionInstance);

        $result = $actionInstance->handle($context, $request);

        return $this->actionSuccessResponse($request, $actionInstance, $result);
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
        [$resourceClass] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        return response()->json($resourceClass::table($request)->get());
    }

    /**
     * Return form schema as JSON (for SPA/AJAX)
     *
     * GET /admin/resource/{slug}/form.json
     */
    public function formJson(Request $request, string $slug)
    {
        [$resourceClass] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        return response()->json($resourceClass::form($request)->rows());
    }

    private function validateActionRequest(Request $request, ActionInterface $action): array
    {
        $rules = $action->rules();

        return empty($rules) ? [] : $request->validate($rules);
    }

    private function authorizeAction(ActionInterface $action, ActionContext $context, string $slug): void
    {
        if (!$action->authorize($context)) {
            throw ResourceException::unauthorized($slug, 'action:' . $action->key());
        }
    }

    private function actionSuccessResponse(Request $request, ActionInterface $action, mixed $result)
    {
        $payload = [
            'status' => 'success',
            'action' => $action->key(),
            'message' => $action->label() . ' completed',
            'result' => $result,
        ];

        if (is_array($result)) {
            if (isset($result['message'])) {
                $payload['message'] = $result['message'];
            }
            if (array_key_exists('redirect', $result)) {
                $payload['redirect'] = $result['redirect'];
            }
            if (array_key_exists('reload', $result)) {
                $payload['reload'] = (bool) $result['reload'];
            }
            // Copy modal form data to payload root for easier frontend access
            if (isset($result['modal_form'])) {
                $payload['modal_form'] = $result['modal_form'];
                if (isset($result['fetch_url'])) {
                    $payload['fetch_url'] = $result['fetch_url'];
                }
                if (isset($result['save_url'])) {
                    $payload['save_url'] = $result['save_url'];
                }
                if (isset($result['title'])) {
                    $payload['title'] = $result['title'];
                }
                if (isset($result['size'])) {
                    $payload['size'] = $result['size'];
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->back()
            ->with('status', $payload['message']);
    }

    private function actionNotFoundResponse(Request $request, string $action)
    {
        $payload = [
            'status' => 'error',
            'message' => "Action '{$action}' not found",
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, 404);
        }

        abort(404, $payload['message']);
    }

    /**
     * Resolve per-page value from session, query parameter, or table default
     */
    protected function savePerPagePreference(Request $request, string $slug, int $perPage): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->session();
        $session->put("ave.per_page.{$slug}", $perPage);
        $session->put("ave.resources.{$slug}.per_page", $perPage);
    }

    /**
     * AJAX endpoint to set per-page preference
     *
     * POST /admin/{slug}/set-per-page
     */
    public function setPerPage(Request $request, string $slug)
    {
        [$resourceClass] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $perPage = (int) $request->input('per_page');
        $table = $resourceClass::table($request);
        $allowedOptions = $table->getPerPageOptions();

        if (!in_array($perPage, $allowedOptions, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid per-page value',
            ], 400);
        }

        $this->savePerPagePreference($request, $slug, $perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Per-page preference saved',
        ]);
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
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request);

        $modelClass = $resourceClass::$model;
        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        try {
            $model = $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ResourceException::modelNotFound($slug, $id);
        }

        $form = $resourceClass::form($request);
        $context = FormContext::forEdit($model, [], $request);

        // Fill fields from model
        foreach ($form->getAllFields() as $field) {
            $field->fillFromDataSource($context->dataSource());
        }

        $formLayout = $form->layout();

        $html = view('ave::partials.modals.form-modal', [
            'form' => $form,
            'formLayout' => $formLayout,
            'model' => $model,
            'context' => $context,
            'slug' => $slug,
            'mode' => 'edit',
        ])->render();

        return response()->json([
            'success' => true,
            'formHtml' => $html,
            'currentData' => $model->toArray(),
        ]);
    }

    /**
     * Get modal form HTML for creating a new record
     *
     * GET /admin/resource/{slug}/modal-form-create
     */
    public function getModalFormCreate(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'create', $request);

        $modelClass = $resourceClass::$model;
        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $model = new $modelClass();

        // Pre-fill model with query parameters for default values
        foreach ($request->query() as $key => $value) {
            $model->$key = $value;
        }

        $form = $resourceClass::form($request);
        $context = FormContext::forCreate([], $request, $model);

        // Fill fields from pre-filled model
        foreach ($form->getAllFields() as $field) {
            $field->fillFromModel($model);
        }

        $formLayout = $form->layout();

        $html = view('ave::partials.modals.form-modal', [
            'form' => $form,
            'formLayout' => $formLayout,
            'model' => $model,
            'context' => $context,
            'slug' => $slug,
            'mode' => 'create',
        ])->render();

        return response()->json([
            'success' => true,
            'formHtml' => $html,
            'currentData' => [],
        ]);
    }

}
