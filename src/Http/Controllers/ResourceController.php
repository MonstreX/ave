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
        protected ResourcePersistence $persistence,
        protected SortableOrderService $sortingService,
        protected RequestDebugSanitizer $requestSanitizer
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

        if (in_array($displayMode, ['tree', 'sortable', 'sortable-grouped'], true)) {
            $structureResult = $this->resolveRecordsForStructureMode(
                $request,
                $table,
                $query,
                $displayMode,
                $slug,
                $modelClass
            );

            $criteriaBadges = array_merge($criteriaBadges, $structureResult['badges']);

            return $this->renderer->index(
                $resourceClass,
                $table,
                $structureResult['records'],
                $request,
                $criteriaBadges
            );
        }

        // Paginate for table mode
        $perPage = $this->resolvePerPage($request, $table, $slug);
        $records = $query->paginate($perPage)->appends($request->query());

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
            $traceId = $this->logValidationFailure(
                stage: 'store',
                resourceClass: $resourceClass,
                slug: $slug,
                request: $request,
                rules: $rules,
                form: $form,
                errors: $exception->errors()
            );

            $errorMessages = $this->formatValidationErrors($exception->errors());

            // Return JSON for AJAX requests (modal forms)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessages,
                    'errors' => $exception->errors(),
                    'trace_id' => $traceId,
                ], 422);
            }

            // Add toast notification for validation errors
            $request->session()->flash('toast', [
                'type' => 'danger',
                'message' => $errorMessages,
                'trace_id' => $traceId,
            ]);

            throw $exception;
        }

        // Hook: Allow resource to modify data before create
        $data = $resourceClass::beforeCreate($data, $request);

        $model = $this->persistence->create($resourceClass, $form, $data, $request, $context);

        // Return JSON for AJAX requests (modal forms)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Created successfully'),
                'reload' => true,
                'data' => $model->fresh()->toArray(),
            ]);
        }

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
            $traceId = $this->logValidationFailure(
                stage: 'update',
                resourceClass: $resourceClass,
                slug: $slug,
                request: $request,
                rules: $rules,
                form: $form,
                model: $model,
                errors: $exception->errors()
            );

            $errorMessages = $this->formatValidationErrors($exception->errors());

            // Return JSON for AJAX requests (modal forms)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessages,
                    'errors' => $exception->errors(),
                    'trace_id' => $traceId,
                ], 422);
            }

            // Add toast notification for validation errors
            $request->session()->flash('toast', [
                'type' => 'danger',
                'message' => $errorMessages,
                'trace_id' => $traceId,
            ]);

            throw $exception;
        }

        $model = $this->persistence->update($resourceClass, $form, $model, $data, $request, $context);

        // Return JSON for AJAX requests (modal forms)
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Updated successfully'),
                'reload' => true,
                'data' => $model->fresh()->toArray(),
            ]);
        }

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

    private function redirectAfterSave(Request $request, string $slug, $model, string $mode)
    {
        $intent = (string) $request->input('_ave_form_action', 'save');
        $statusMessage = $mode === 'edit' ? __('Updated successfully') : __('Created successfully');

        if ($intent === 'save-continue') {
            return redirect()
                ->route('ave.resource.edit', ['slug' => $slug, 'id' => $model->getKey()])
                ->with('status', $statusMessage);
        }

        // Build route params with resource-specific additions
        $resourceClass = $this->resources->resource($slug);
        $customParams = $resourceClass ? $resourceClass::getIndexRedirectParams($model, $request, $mode) : [];
        $routeParams = array_merge(['slug' => $slug], $customParams);

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
        $table = $resourceClass::table($request);

        $this->validateSortableMode($table, $slug);

        $orderColumn = $table->getOrderColumn() ?? 'order';
        $order = $this->normalizeOrderInput($request, $orderColumn);

        $this->sortingService->reorder(
            $order,
            $orderColumn,
            $resource,
            $request->user(),
            $modelClass,
            $slug
        );

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
        ]);
    }

    /**
     * Validate that the resource supports sortable mode
     */
    protected function validateSortableMode($table, string $slug): void
    {
        $displayMode = $table->getDisplayMode();
        if (!in_array($displayMode, ['sortable', 'sortable-grouped'])) {
            throw new ResourceException("Resource '{$slug}' does not support sortable mode", 422);
        }
    }

    /**
     * Normalize order input from different formats to unified format
     * Supports both old format (items array) and new format (order object)
     */
    protected function normalizeOrderInput(Request $request, string $orderColumn): array
    {
        if ($request->has('order')) {
            // New format: { order: { id1: pos1, id2: pos2 }, order_column: 'order' }
            $validated = $request->validate([
                'order' => 'required|array',
                'order.*' => 'required|integer',
                'order_column' => 'nullable|string',
                'group_id' => 'nullable|integer',
            ]);
            return $validated['order'];
        }

        // Old format: { items: [{ id: 1, order: 1 }] }
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            "items.*.{$orderColumn}" => 'required|integer',
        ]);

        // Convert to new format
        $order = [];
        foreach ($validated['items'] as $item) {
            $order[$item['id']] = $item[$orderColumn];
        }
        return $order;
    }

    /**
     * Update item's group assignment
     *
     * POST /admin/resource/{slug}/update-group
     */
    public function updateGroup(Request $request, string $slug)
    {
        [$resourceClass, $resource] = $this->resolveAndAuthorize($slug, 'update', $request);

        $modelClass = $resourceClass::$model;
        $table = $resourceClass::table($request);

        if ($table->getDisplayMode() !== 'sortable-grouped') {
            throw new ResourceException("Resource '{$slug}' does not support grouped sortable mode", 422);
        }

        $groupColumn = $table->getGroupByColumn();

        if (!$groupColumn) {
            throw new ResourceException("No group column configured for resource '{$slug}'", 422);
        }

        $validated = $request->validate([
            'item_id' => 'required|integer',
            'group_column' => 'required|string',
            'group_id' => 'required|integer',
        ]);

        // Verify the group column matches the table configuration
        if ($validated['group_column'] !== $groupColumn) {
            throw new ResourceException("Invalid group column specified", 422);
        }

        $model = $modelClass::findOrFail($validated['item_id']);
        $user = $request->user();

        if (!$resource->can('update', $user, $model)) {
            throw ResourceException::unauthorized($slug, 'update');
        }

        // Update group assignment
        $oldGroupId = $model->getAttribute($groupColumn);
        $model->setAttribute($groupColumn, $validated['group_id']);
        $model->save();


        return response()->json([
            'success' => true,
            'message' => 'Item moved to new group successfully',
            'old_group' => $oldGroupId,
            'new_group' => $validated['group_id'],
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

        $table = $resourceClass::table($request);

        if ($table->getDisplayMode() !== 'tree') {
            throw new ResourceException("Resource '{$slug}' does not support tree mode", 422);
        }

        $validated = $request->validate([
            'tree' => 'required|array',
        ]);

        $modelClass = $resourceClass::$model;
        $treePayload = $this->normalizeTreePayload($validated['tree']);
        $this->sortingService->rebuildTree(
            $treePayload,
            $table,
            $resource,
            $request->user(),
            $modelClass,
            $slug
        );

        return response()->json([
            'success' => true,
            'message' => 'Tree structure updated successfully',
        ]);
    }

    /**
     * Normalize and validate tree payload.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<int,array<string,mixed>>
     */
    protected function normalizeTreePayload(array $items): array
    {
        return array_map(function ($item) {
            if (!isset($item['id'])) {
                throw new ResourceException('Tree payload is missing node id', 422);
            }

            $normalized = [
                'id' => (int) $item['id'],
            ];

            if (isset($item['children'])) {
                if (!is_array($item['children'])) {
                    throw new ResourceException('Tree node children must be an array', 422);
                }

                $normalized['children'] = $this->normalizeTreePayload($item['children']);
            }

            return $normalized;
        }, $items);
    }

    /**
     * Collect keys of fields marked as sensitive on the form.
     *
     * @return array<int,string>
     */
    protected function gatherSensitiveFieldKeys(Form $form): array
    {
        $keys = [];

        foreach ($form->getAllFields() as $field) {
            if (method_exists($field, 'isSensitive') && $field->isSensitive()) {
                $keys[] = $field->key();
                $keys[] = $field->baseKey();

                if (method_exists($field, 'getStatePath')) {
                    $keys[] = $field->getStatePath();
                }
            }
        }

        return array_values(array_filter(array_unique($keys)));
    }

    /**
     * Log validation failures with sanitized payload per .doc/CODE-REVIEW-RULES security section.
     */
    protected function logValidationFailure(
        string $stage,
        string $resourceClass,
        string $slug,
        Request $request,
        array $rules,
        Form $form,
        ?Model $model = null,
        array $errors = []
    ): string {
        $traceId = (string) Str::uuid();
        $sensitiveKeys = $this->gatherSensitiveFieldKeys($form);

        $sanitizedInput = $this->requestSanitizer->sanitize($request, $sensitiveKeys);

        Log::error("Resource validation failed on {$stage}", [
            'trace_id' => $traceId,
            'resource' => $resourceClass,
            'slug' => $slug,
            'model_id' => $model?->getKey(),
            'errors' => $errors,
            'input' => $sanitizedInput,
            'rules' => array_keys($rules),
        ]);

        return $traceId;
    }

    /**
     * Get modal form HTML for editing a record
     *
     * GET /admin/resource/{slug}/{id}/modal-form
     */
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

    /**
     * Resolve records for tree / sortable displays while enforcing instant-load limits.
     *
     * @return array{records: LengthAwarePaginator, badges: array<int,array<string,mixed>>}
     */
    protected function resolveRecordsForStructureMode(
        Request $request,
        Table $table,
        Builder $baseQuery,
        string $displayMode,
        string $slug,
        string $modelClass
    ): array {
        $limit = max(1, $table->getMaxInstantLoad());
        $forceLimit = $table->shouldForceInstantLoadLimit();
        $orderedQuery = clone $baseQuery;

        if ($displayMode === 'sortable-grouped') {
            $groupByColumn = $table->getGroupByColumn();
            $groupByRelation = $table->getGroupByRelation();
            $groupOrderColumn = $table->getGroupByOrderColumn();
            $itemOrderColumn = $table->getOrderColumn() ?? 'order';

            if ($groupByRelation) {
                $orderedQuery->with($groupByRelation);
                $model = new $modelClass();
                $relation = $model->{$groupByRelation}();
                $relatedTable = $relation->getRelated()->getTable();
                $foreignKey = $groupByColumn;
                $ownerKey = $relation->getOwnerKeyName();

                $orderedQuery->join(
                    $relatedTable,
                    "{$model->getTable()}.{$foreignKey}",
                    '=',
                    "{$relatedTable}.{$ownerKey}"
                )
                    ->orderBy("{$relatedTable}.{$groupOrderColumn}")
                    ->orderBy("{$model->getTable()}.{$itemOrderColumn}")
                    ->select("{$model->getTable()}.*");
            } else {
                $orderedQuery->orderBy($groupByColumn)
                    ->orderBy($itemOrderColumn);
            }
        } else {
            $orderColumn = $table->getOrderColumn() ?? 'order';
            $orderedQuery->orderBy($orderColumn);
        }

        // Load up to limit + 1 rows to detect overflow without fetching the whole dataset.
        $records = $orderedQuery
            ->limit($limit + 1)
            ->get();

        $limitExceeded = $records->count() > $limit;

        if ($limitExceeded && $forceLimit) {
            // Per .doc/CORE-CHEKLIST.md, we must keep tree payloads cacheable and predictable.
            throw new ResourceException(
                "Resource '{$slug}' exceeds instant load limit of {$limit}. Apply filters or switch to paginated mode.",
                422
            );
        }

        if ($limitExceeded) {
            $records = $records->take($limit)->values();
        }

        $paginator = new LengthAwarePaginator(
            $records,
            $limitExceeded ? $limit + 1 : $records->count(),
            max($records->count(), 1),
            1,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $badges = [];
        if ($limitExceeded) {
            $badges[] = [
                'label' => "Limited to first {$limit} records (instant load cap)",
                'key' => 'instant-load-limit',
                'value' => $limit,
                'variant' => 'limit-warning',
            ];
        }

        return [
            'records' => $paginator,
            'badges' => $badges,
        ];
    }
}
