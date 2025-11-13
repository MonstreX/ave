<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Criteria\CriteriaPipeline;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Core\Columns\BooleanColumn;
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
    public function __construct(
        protected ResourceManager $resources,
        protected ResourceRenderer $renderer,
        protected FormValidator $validator,
        protected ResourcePersistence $persistence
    ) {}

    /**
     * Resolve resource class and check authorization
     *
     * @throws ResourceException
     */
    private function resolveAndAuthorize(
        string $slug,
        string $ability,
        Request $request,
        mixed $model = null
    ): array {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        $resource = new $resourceClass();
        if (!$resource->can($ability, $request->user(), $model)) {
            throw ResourceException::unauthorized($slug, $ability);
        }

        return [$resourceClass, $resource];
    }

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
    private function formatValidationErrors(array $errors): string
    {
        if (empty($errors)) {
            return 'Validation failed. Please check the form for errors.';
        }

        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $messages[] = $error;
                }
            }
        }

        if (empty($messages)) {
            return 'Validation failed. Please check the form for errors.';
        }

        // Limit to first 3 errors to avoid extremely long toast messages
        if (count($messages) > 3) {
            $messages = array_slice($messages, 0, 3);
            $messages[] = sprintf('... and %d more error(s)', count($errors) - 3);
        }

        return implode("\n", $messages);
    }

    /**
     * Display resource index page with table
     *
     * GET /admin/resource/{slug}
     */
    public function index(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'viewAny', $request);

        $table = $resourceClass::table($request);
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $query = $modelClass::query();

        $request->attributes->set('ave.resource.class', $resourceClass);
        $request->attributes->set('ave.resource.instance', $resource);
        $request->attributes->set('ave.resource.model', $modelClass);

        // Apply eager loading
        $resourceClass::applyEagerLoading($query);

        $criteriaPipeline = CriteriaPipeline::make($resourceClass, $table, $request);
        $query = $criteriaPipeline->apply($query);
        $criteriaBadges = $criteriaPipeline->badges();

        // Check display mode
        $displayMode = $table->getDisplayMode();

        // For tree view - load all records without pagination
        if ($displayMode === 'tree' || $displayMode === 'sortable') {
            $orderColumn = $table->getOrderColumn() ?? 'order';
            $allRecords = $query->orderBy($orderColumn)->get();
            $count = $allRecords->count();

            // Create fake paginator for compatibility
            // perPage must be at least 1 to avoid division by zero
            $records = new \Illuminate\Pagination\LengthAwarePaginator(
                $allRecords,
                $count,
                max($count, 1),
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return $this->renderer->index($resourceClass, $table, $records, $request, $criteriaBadges);
        }

        // Paginate for table mode
        $perPage = $this->resolvePerPage($request, $table, $slug);

        // Check if loadAll mode is enabled and within limits
        if ($table->shouldLoadAll()) {
            $maxLoadAll = $table->getMaxLoadAll() ?? config('ave.pagination.max_load_all', 1000);
            $totalCount = $query->count();

            if ($totalCount <= $maxLoadAll) {
                // Load all records and create manual paginator
                $allRecords = $query->get();
                $records = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allRecords,
                    $totalCount,
                    $totalCount,
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                // Fallback to regular pagination if exceeds limit
                $records = $query->paginate($perPage)->appends($request->query());
            }
        } else {
            $records = $query->paginate($perPage)->appends($request->query());
        }

        return $this->renderer->index($resourceClass, $table, $records, $request, $criteriaBadges);
    }

    /**
     * Show form for creating new resource
     *
     * GET /admin/resource/{slug}/create
     */
    public function create(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'create', $request);

        $form = $resourceClass::form($request);
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $model = new $modelClass();

        return $this->renderer->form($resourceClass, $form, $model, $request);
    }

    /**
     * Store newly created resource
     *
     * POST /admin/resource/{slug}
     */
    public function store(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'create', $request);

        $form = $resourceClass::form($request);
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $blankModel = new $modelClass();

        $context = FormContext::forCreate([], $request, $blankModel);
        $rules = $this->validator->rulesFromForm(
            $form,
            $resourceClass,
            $request,
            mode: 'create',
            model: $blankModel,
            context: $context
        );
        try {
            $data = $request->validate($rules);
        } catch (ValidationException $exception) {
            Log::error('Resource validation failed on store', [
                'resource' => $resourceClass,
                'slug' => $slug,
                'errors' => $exception->errors(),
                'input' => $request->all(),
                'rules' => $rules,
            ]);

            // Add toast notification for validation errors
            $errorMessages = $this->formatValidationErrors($exception->errors());
            $request->session()->flash('toast', [
                'type' => 'danger',
                'message' => $errorMessages,
            ]);

            throw $exception;
        }

        // Auto-fill menu_id from URL if present and not in data
        if ($slug === 'menu-items' && $request->has('menu_id') && !isset($data['menu_id'])) {
            $data['menu_id'] = $request->get('menu_id');
        }

        $model = $this->persistence->create($resourceClass, $form, $data, $request, $context);

        return $this->redirectAfterSave($request, $slug, $model, 'create');
    }

    /**
     * Show form for editing resource
     *
     * GET /admin/resource/{slug}/{id}/edit
     */
    public function edit(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        try {
            $model = $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ResourceException::modelNotFound($slug, $id);
        }

        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request, $model);

        $form = $resourceClass::form($request);

        return $this->renderer->form($resourceClass, $form, $model, $request);
    }

    /**
     * Update existing resource
     *
     * PUT/PATCH /admin/resource/{slug}/{id}
     */
    public function update(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        try {
            $model = $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ResourceException::modelNotFound($slug, $id);
        }

        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request, $model);

        $form = $resourceClass::form($request);
        $context = FormContext::forEdit($model, [], $request);
        $rules = $this->validator->rulesFromForm($form, $resourceClass, $request, mode: 'edit', model: $model, context: $context);
        try {
            $data = $request->validate($rules);
        } catch (ValidationException $exception) {
            Log::error('Resource validation failed on update', [
                'resource' => $resourceClass,
                'slug' => $slug,
                'model_id' => $model->getKey(),
                'errors' => $exception->errors(),
                'input' => $request->all(),
                'rules' => $rules,
            ]);

            // Add toast notification for validation errors
            $errorMessages = $this->formatValidationErrors($exception->errors());
            $request->session()->flash('toast', [
                'type' => 'danger',
                'message' => $errorMessages,
            ]);

            throw $exception;
        }

        $model = $this->persistence->update($resourceClass, $form, $model, $data, $request, $context);

        return $this->redirectAfterSave($request, $slug, $model, 'edit');
    }

    /**
     * Inline update handler for AJAX table interactions
     */
    public function inlineUpdate(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        $model = $this->findModelOrFail($resourceClass, $slug, $id);
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request, $model);

        $field = (string) $request->input('field', '');
        if ($field === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Field is required.',
            ], 422);
        }

        $table = $resourceClass::table($request);
        $column = $table->findInlineColumn($field);

        if (!$column) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field is not inline editable.',
            ], 422);
        }

        $rules = $column->inlineValidationRules();
        if ($rules) {
            $validated = $request->validate(['value' => $rules]);
            $value = $validated['value'];
        } else {
            $value = $request->input('value');
        }

        if ($column instanceof BooleanColumn) {
            $value = $this->resolveBooleanValue($column, $model, $value);
        }

        data_set($model, $field, $value);
        $model->save();
        $model->refresh();

        $raw = $column->resolveRecordValue($model);
        $formatted = $column->formatValue($raw, $model);
        $canonical = $raw;
        $state = null;

        if ($column instanceof BooleanColumn) {
            $state = $column->isActive($raw);
            $canonical = $state
                ? (string) $column->getTrueValue()
                : (string) $column->getFalseValue();
        }

        return response()->json([
            'status' => 'success',
            'field' => $field,
            'value' => $raw,
            'formatted' => $formatted,
            'canonical' => $canonical,
            'state' => $state,
        ]);
    }

    protected function resolveBooleanValue(BooleanColumn $column, mixed $model, mixed $payload): mixed
    {
        if ($payload === null || $payload === '') {
            $current = $column->resolveRecordValue($model);

            return $column->isActive($current)
                ? $column->getFalseValue()
                : $column->getTrueValue();
        }

        return (string) $payload === (string) $column->getTrueValue()
            ? $column->getTrueValue()
            : $column->getFalseValue();
    }

    /**
     * Delete resource
     *
     * DELETE /admin/resource/{slug}/{id}
     */
    public function destroy(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        try {
            $model = $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ResourceException::modelNotFound($slug, $id);
        }

        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'delete', $request, $model);

        $this->persistence->delete($resourceClass, $model);

        return redirect()
            ->route('ave.resource.index', ['slug' => $slug])
            ->with('status', 'Deleted successfully');
    }

    /**
     * Return table schema as JSON (for SPA/AJAX)
     *
     * GET /admin/resource/{slug}/table.json
     */
    public function tableJson(Request $request, string $slug)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        return response()->json($resourceClass::table($request)->get());
    }

    /**
     * Return form schema as JSON (for SPA/AJAX)
     *
     * GET /admin/resource/{slug}/form.json
     */
    public function formJson(Request $request, string $slug)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        return response()->json($resourceClass::form($request)->rows());
    }

    private function findModelOrFail(string $resourceClass, string $slug, string $id)
    {
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        try {
            return $modelClass::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ResourceException::modelNotFound($slug, $id);
        }
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
        }

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->back()
            ->with('status', $payload['message']);
    }

    private function redirectAfterSave(Request $request, string $slug, $model, string $mode)
    {
        $intent = (string) $request->input('_ave_form_action', 'save');
        $statusMessage = $mode === 'edit' ? __('Updated successfully') : __('Created successfully');

        if ($intent === 'save-continue') {
            return redirect()
                ->route('ave.resource.edit', ['slug' => $slug, 'id' => $model->getKey()])
                ->with('status', $statusMessage);
        }

        // For menu-items, redirect back with menu_id filter
        $routeParams = ['slug' => $slug];
        if ($slug === 'menu-items' && isset($model->menu_id)) {
            $routeParams['menu_id'] = $model->menu_id;
        }

        return redirect()
            ->route('ave.resource.index', $routeParams)
            ->with('status', $statusMessage)
            ->with('model_id', $model->getKey());
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
    protected function resolvePerPage(Request $request, Table $table, string $slug): int
    {
        // Priority 1: Query parameter ?per_page=X
        $requestedPerPage = (int) $request->query('per_page', 0);

        // Priority 2: Session value (only if session exists)
        if ($requestedPerPage === 0 && $request->hasSession()) {
            $requestedPerPage = (int) $request->session()->get("ave.per_page.{$slug}", 0);
        }

        // Validate against allowed options
        $allowedOptions = $table->getPerPageOptions();

        if ($requestedPerPage > 0 && in_array($requestedPerPage, $allowedOptions, true)) {
            // Save to session if it came from query parameter
            if ($request->has('per_page') && $request->hasSession()) {
                $this->savePerPagePreference($request, $slug, $requestedPerPage);
            }
            return $requestedPerPage;
        }

        // Priority 3: Table default
        return $table->getPerPage();
    }

    /**
     * Save per-page preference to session
     */
    protected function savePerPagePreference(Request $request, string $slug, int $perPage): void
    {
        $request->session()->put("ave.per_page.{$slug}", $perPage);
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
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request);

        $modelClass = $resourceClass::$model;
        $table = $resourceClass::table(null);
        $orderColumn = $table->getOrderColumn() ?? 'order';

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            "items.*.{$orderColumn}" => 'required|integer',
        ]);

        // Check authorization for each item
        foreach ($validated['items'] as $itemData) {
            $model = $modelClass::findOrFail($itemData['id']);
            if (!$resource->can('update', $request->user(), $model)) {
                throw ResourceException::unauthorized($slug, 'update');
            }
        }

        // Update order for all items
        foreach ($validated['items'] as $itemData) {
            $modelClass::where('id', $itemData['id'])
                ->update([$orderColumn => $itemData[$orderColumn]]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
        ]);
    }

    /**
     * Update tree structure (parent_id and order)
     *
     * POST /admin/resource/{slug}/update-tree
     */
    public function updateTree(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request);

        $validated = $request->validate([
            'tree' => 'required|array',
            'parent_column' => 'required|string',
            'order_column' => 'required|string',
        ]);

        $modelClass = $resourceClass::$model;
        $parentCol = $validated['parent_column'];
        $orderCol = $validated['order_column'];

        // Process tree recursively
        $this->processTreeItems(
            $modelClass,
            $validated['tree'],
            null,
            0,
            $parentCol,
            $orderCol,
            $resource,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tree structure updated successfully',
        ]);
    }

    /**
     * Recursively process tree items
     */
    protected function processTreeItems(
        string $modelClass,
        array $items,
        $parentId,
        int $order,
        string $parentCol,
        string $orderCol,
        $resource,
        $user
    ): void {
        foreach ($items as $index => $item) {
            $model = $modelClass::findOrFail($item['id']);

            // Check authorization
            if (!$resource->can('update', $user, $model)) {
                throw ResourceException::unauthorized('resource', 'update');
            }

            // Update parent_id and order
            $modelClass::where('id', $item['id'])
                ->update([
                    $parentCol => $parentId,
                    $orderCol => $order + $index,
                ]);

            // Process children recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $this->processTreeItems(
                    $modelClass,
                    $item['children'],
                    $item['id'],
                    0,
                    $parentCol,
                    $orderCol,
                    $resource,
                    $user
                );
            }
        }
    }
}
