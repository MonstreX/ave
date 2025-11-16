<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Actions;

use Monstrex\Ave\Support\CleanJsonResponse;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Columns\BooleanColumn;
use Monstrex\Ave\Core\ResourceManager;
use Monstrex\Ave\Exceptions\ResourceException;

class InlineUpdateAction extends AbstractResourceAction
{
    public function __construct(ResourceManager $resources)
    {
        parent::__construct($resources);
    }

    public function __invoke(Request $request, string $slug, string $id)
    {
        $resourceClass = $this->resolveResourceClass($slug);

        $model = $this->findModelOrFail($resourceClass, $slug, $id);
        [, $resource] = $this->resolveAndAuthorize($slug, 'update', $request, $model);

        $field = (string) $request->input('field', '');
        if ($field === '') {
            return CleanJsonResponse::make([
                'status' => 'error',
                'message' => 'Field is required.',
            ], 422);
        }

        $table = $resourceClass::table($request);
        $column = $table->findInlineColumn($field);

        if (!$column) {
            return CleanJsonResponse::make([
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

        return CleanJsonResponse::make([
            'status' => 'success',
            'field' => $field,
            'value' => $raw,
            'formatted' => $formatted,
            'canonical' => $canonical,
            'state' => $state,
        ]);
    }

    private function resolveBooleanValue(BooleanColumn $column, mixed $model, mixed $payload): mixed
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
}
