<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Concerns;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Exceptions\ResourceException;

trait InteractsWithResources
{
    protected function resolveAndAuthorize(
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

    protected function resolveResourceClass(string $slug): string
    {
        $resourceClass = $this->resources->resource($slug);

        if (!$resourceClass) {
            throw ResourceException::notFound($slug);
        }

        return $resourceClass;
    }

    protected function findModelOrFail(string $resourceClass, string $slug, string $id): mixed
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

    protected function formatValidationErrors(array $errors): string
    {
        if (empty($errors)) {
            return __('ave::errors.validation_failed');
        }

        $messages = [];
        foreach ($errors as $fieldErrors) {
            if (is_array($fieldErrors)) {
                foreach ($fieldErrors as $error) {
                    $messages[] = $error;
                }
            }
        }

        if (empty($messages)) {
            return __('ave::errors.validation_failed');
        }

        // Use try-catch to handle cases where config is not available (e.g., unit tests)
        $maxErrors = 3;
        try {
            $maxErrors = config('ave.validation.max_errors_display', 3);
        } catch (\Throwable $e) {
            // Config not available, use default
        }

        if (count($messages) > $maxErrors) {
            $remaining = count($messages) - $maxErrors;
            $messages = array_slice($messages, 0, $maxErrors);
            $messages[] = sprintf('... and %d more error(s)', $remaining);
        }

        return implode("\n", $messages);
    }

    protected function normalizeOrderInput(Request $request, string $orderColumn): array
    {
        if ($request->has('order')) {
            $validated = $request->validate([
                'order' => 'required|array',
                'order.*' => 'required|integer',
                'order_column' => 'nullable|string',
                'group_id' => 'nullable|integer',
            ]);
            return $validated['order'];
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            "items.*.{$orderColumn}" => 'required|integer',
        ]);

        $order = [];
        foreach ($validated['items'] as $item) {
            $order[$item['id']] = $item[$orderColumn];
        }

        return $order;
    }

    protected function validateSortableMode(Table $table, string $slug): void
    {
        $displayMode = $table->getDisplayMode();
        if (!in_array($displayMode, ['sortable', 'sortable-grouped'], true)) {
            throw new ResourceException("Resource '{$slug}' does not support sortable mode", 422);
        }
    }

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

    protected function redirectAfterSave(Request $request, string $slug, mixed $model, string $mode)
    {
        $intent = (string) $request->input('_ave_form_action', 'save');
        $statusMessage = $mode === 'edit' ? __('ave::common.updated_successfully') : __('ave::common.created_successfully');

        if ($intent === 'save-continue') {
            return redirect()
                ->route('ave.resource.edit', ['slug' => $slug, 'id' => $model->getKey()])
                ->with('status', $statusMessage);
        }

        $resourceClass = $this->resources->resource($slug);
        $customParams = $resourceClass ? $resourceClass::getIndexRedirectParams($model, $request, $mode) : [];
        $routeParams = array_merge(['slug' => $slug], $customParams);

        return redirect()
            ->route('ave.resource.index', $routeParams)
            ->with('status', $statusMessage)
            ->with('model_id', $model->getKey());
    }
}
