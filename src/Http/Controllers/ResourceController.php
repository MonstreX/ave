<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Core\Validation\FormValidator;
use Monstrex\Ave\Core\Persistence\ResourcePersistence;
use Monstrex\Ave\Core\Rendering\ResourceRenderer;
use Monstrex\Ave\Core\Query\TableQueryBuilder;
use Monstrex\Ave\Exceptions\ResourceException;

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
     * Display resource index page with table
     *
     * GET /admin/resource/{slug}
     */
    public function index(Request $request, string $slug)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        // Authorization check
        $resource = new $resourceClass();
        if (!$resource->can('viewAny', $request->user())) {
            throw ResourceException::unauthorized($slug, 'viewAny');
        }

        $table = $resourceClass::table($request);
        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        $query = $modelClass::query();

        // Apply eager loading
        $resourceClass::applyEagerLoading($query);

        // Apply table query builder (search, filters, sort)
        $query = TableQueryBuilder::apply($query, $table, $request);

        // Paginate
        $perPage = TableQueryBuilder::getPerPage($table);
        $records = $query->paginate($perPage)->appends($request->query());

        return $this->renderer->index($resourceClass, $table, $records, $request);
    }

    /**
     * Show form for creating new resource
     *
     * GET /admin/resource/{slug}/create
     */
    public function create(Request $request, string $slug)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        // Authorization check
        $resource = new $resourceClass();
        if (!$resource->can('create', $request->user())) {
            throw ResourceException::unauthorized($slug, 'create');
        }

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
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        // Authorization check
        $resource = new $resourceClass();
        if (!$resource->can('create', $request->user())) {
            throw ResourceException::unauthorized($slug, 'create');
        }

        $form = $resourceClass::form($request);
        $rules = $this->validator->rulesFromForm($form, $resourceClass, $request, mode: 'create');
        $data = $request->validate($rules);

        $model = $this->persistence->create($resourceClass, $form, $data, $request);

        return redirect()
            ->route('ave.resource.index', ['slug' => $slug])
            ->with('status', 'Created successfully')
            ->with('model_id', $model->getKey());
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

        // Authorization check
        $resource = new $resourceClass();
        if (!$resource->can('update', $request->user(), $model)) {
            throw ResourceException::unauthorized($slug, 'update');
        }

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

        // Authorization check
        $resource = new $resourceClass();
        if (!$resource->can('update', $request->user(), $model)) {
            throw ResourceException::unauthorized($slug, 'update');
        }

        $form = $resourceClass::form($request);
        $rules = $this->validator->rulesFromForm($form, $resourceClass, $request, mode: 'edit', model: $model);
        $data = $request->validate($rules);

        $this->persistence->update($resourceClass, $form, $model, $data, $request);

        return redirect()
            ->route('ave.resource.index', ['slug' => $slug])
            ->with('status', 'Updated successfully');
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

        // Authorization check
        $resource = new $resourceClass();
        if (!$resource->can('delete', $request->user(), $model)) {
            throw ResourceException::unauthorized($slug, 'delete');
        }

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

    /**
     * Execute bulk action on selected resources
     *
     * POST /admin/resource/{slug}/bulk
     */
    public function bulk(Request $request, string $slug)
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        // Validate input
        $validated = $request->validate([
            'action' => 'required|string',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'numeric',
        ]);

        $modelClass = $resourceClass::$model;

        if (!$modelClass) {
            throw ResourceException::invalidModel($resourceClass);
        }

        // Get the action
        $action = $validated['action'];
        $ids = $validated['ids'];

        // Get table configuration to find the action
        $table = $resourceClass::table($request);
        $tableConfig = $table->get();
        $bulkActions = $tableConfig['bulkActions'] ?? [];

        // Find matching bulk action
        $bulkAction = null;
        foreach ($bulkActions as $ba) {
            if ($ba->key() === $action) {
                $bulkAction = $ba;
                break;
            }
        }

        if (!$bulkAction) {
            return response()->json(['error' => "Action '{$action}' not found"], 404);
        }

        // Authorization check for each model
        $resource = new $resourceClass();
        foreach ($ids as $id) {
            try {
                $model = $modelClass::findOrFail($id);
                if (!$resource->can('update', $request->user(), $model)) {
                    throw ResourceException::unauthorized($slug, 'bulk:' . $action);
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw ResourceException::modelNotFound($slug, $id);
            }
        }

        // Get models and execute action
        $models = $modelClass::whereIn($modelClass::make()->getKeyName(), $ids)->get();

        // Execute bulk action
        $bulkAction->execute($models, $request);

        return response()->json([
            'status' => 'success',
            'message' => "Bulk action '{$action}' executed on " . count($ids) . " items",
            'count' => count($ids),
        ]);
    }
}
