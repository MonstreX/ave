<?php

namespace Monstrex\Ave\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Query\TableQueryBuilder;

/**
 * Controller for managing resources (CRUD operations)
 */
class ResourceController extends Controller
{
    protected string $resourceClass;

    /**
     * Set the resource class for the controller
     */
    public function setResource(string $resourceClass): void
    {
        $this->resourceClass = $resourceClass;
    }

    /**
     * Get list of resource records
     */
    public function index(Request $request)
    {
        $resource = $this->getResourceInstance();
        $this->authorize('viewAny', $resource);

        $query = $resource->newQuery();
        $tableConfig = $resource->table();

        // Build query using TableQueryBuilder
        $queryBuilder = TableQueryBuilder::for($query)
            ->search($request->input('search', ''))
            ->filters($request->input('filters', []))
            ->sort(
                $request->input('sort_column'),
                $request->input('sort_direction', 'asc')
            )
            ->page((int) $request->input('page', 1))
            ->perPage((int) $request->input('per_page', $tableConfig->getPerPage()));

        // Apply search with configured columns
        $searchableColumns = $tableConfig->getSearchable();
        if (!empty($searchableColumns)) {
            $queryBuilder->applySearch($searchableColumns);
        }

        // Apply filters
        $filters = $tableConfig->getFilters();
        if (!empty($filters)) {
            $queryBuilder->applyFilters($filters);
        }

        // Apply sorting
        $queryBuilder->applySort();

        // Get paginated results
        $paginator = $queryBuilder->paginate();

        return [
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'resource' => [
                'slug' => $resource->slug(),
                'label' => $resource->label(),
                'columns' => $tableConfig->getColumns(),
                'filters' => $tableConfig->getFilters(),
                'actions' => $tableConfig->getActions(),
                'bulkActions' => $tableConfig->getBulkActions(),
            ],
        ];
    }

    /**
     * Show form for creating a new record
     */
    public function create(Request $request)
    {
        $resource = $this->getResourceInstance();
        $this->authorize('create', $resource);

        $formConfig = $resource->form();

        return [
            'form' => [
                'fields' => $formConfig->getFields(),
                'layout' => $formConfig->getLayout(),
                'submitLabel' => $formConfig->getSubmitLabel(),
                'cancelUrl' => $formConfig->getCancelUrl(),
            ],
            'resource' => [
                'slug' => $resource->slug(),
                'label' => $resource->label(),
            ],
        ];
    }

    /**
     * Store a new record in the database
     */
    public function store(Request $request)
    {
        $resource = $this->getResourceInstance();
        $this->authorize('create', $resource);

        $formConfig = $resource->form();
        $fields = $formConfig->getFields();

        // Extract and validate data
        $data = [];
        foreach ($fields as $field) {
            $value = $request->input($field->key());
            $data[$field->key()] = $field->extract($value);
        }

        // Validate data
        $validator = validator($data, $this->getValidationRules($fields));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create resource
        $model = $resource->getModel();
        $instance = $model->create($data);

        return [
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => $instance,
        ];
    }

    /**
     * Show form for editing a record
     */
    public function edit(Request $request, $id)
    {
        $resource = $this->getResourceInstance();
        $model = $resource->getModel()->findOrFail($id);

        $this->authorize('update', $model);

        $formConfig = $resource->form();

        return [
            'form' => [
                'fields' => $formConfig->getFields(),
                'layout' => $formConfig->getLayout(),
                'submitLabel' => $formConfig->getSubmitLabel(),
                'cancelUrl' => $formConfig->getCancelUrl(),
            ],
            'resource' => [
                'slug' => $resource->slug(),
                'label' => $resource->label(),
                'id' => $id,
            ],
            'data' => $model->toArray(),
        ];
    }

    /**
     * Update a record in the database
     */
    public function update(Request $request, $id)
    {
        $resource = $this->getResourceInstance();
        $model = $resource->getModel()->findOrFail($id);

        $this->authorize('update', $model);

        $formConfig = $resource->form();
        $fields = $formConfig->getFields();

        // Extract data
        $data = [];
        foreach ($fields as $field) {
            $value = $request->input($field->key());
            if ($value !== null) {
                $data[$field->key()] = $field->extract($value);
            }
        }

        // Validate data
        $validator = validator($data, $this->getValidationRules($fields));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update resource
        $model->update($data);

        return [
            'success' => true,
            'message' => 'Resource updated successfully',
            'data' => $model,
        ];
    }

    /**
     * Delete a record from the database
     */
    public function destroy(Request $request, $id)
    {
        $resource = $this->getResourceInstance();
        $model = $resource->getModel()->findOrFail($id);

        $this->authorize('delete', $model);

        $model->delete();

        return [
            'success' => true,
            'message' => 'Resource deleted successfully',
        ];
    }

    /**
     * Execute a row action on a resource
     */
    public function executeAction(Request $request, $id)
    {
        $resource = $this->getResourceInstance();
        $model = $resource->getModel()->findOrFail($id);

        $this->authorize('update', $model);

        $actionKey = $request->input('action');
        $tableConfig = $resource->table();
        $actions = $tableConfig->getActions();

        $action = collect($actions)->firstWhere('key', $actionKey);
        if (!$action) {
            return response()->json(['error' => 'Action not found'], 404);
        }

        $result = $action->execute($model);

        return [
            'success' => true,
            'message' => 'Action executed successfully',
            'result' => $result,
        ];
    }

    /**
     * Execute a bulk action on multiple resources
     */
    public function executeBulkAction(Request $request)
    {
        $resource = $this->getResourceInstance();
        $this->authorize('delete', $resource);

        $actionKey = $request->input('action');
        $ids = $request->input('ids', []);

        $tableConfig = $resource->table();
        $bulkActions = $tableConfig->getBulkActions();

        $bulkAction = collect($bulkActions)->firstWhere('key', $actionKey);
        if (!$bulkAction) {
            return response()->json(['error' => 'Bulk action not found'], 404);
        }

        $result = $bulkAction->execute($ids);

        return [
            'success' => true,
            'message' => 'Bulk action executed successfully',
            'result' => $result,
        ];
    }

    /**
     * Get an instance of the resource
     */
    protected function getResourceInstance(): Resource
    {
        return new $this->resourceClass();
    }

    /**
     * Get validation rules from fields
     */
    protected function getValidationRules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field->key()] = $field->getRules();
        }
        return $rules;
    }

    /**
     * Check authorization for an action
     */
    protected function authorize(string $ability, $model): void
    {
        if (method_exists($model, 'authorize')) {
            if (!$model->authorize($ability)) {
                abort(403, 'Unauthorized');
            }
        }
    }
}
