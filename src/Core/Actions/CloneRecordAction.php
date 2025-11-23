<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Illuminate\Support\Arr;

class CloneRecordAction extends BaseAction implements RowAction
{
    protected string $key = 'clone';
    protected ?string $icon = 'voyager-paste';
    protected string $color = 'secondary';
    protected ?string $ability = 'update';

    public function label(): string
    {
        return __('ave::actions.clone');
    }

    public function confirm(): ?string
    {
        return __('ave::actions.clone_confirm');
    }

    public function authorize(ActionContext $context): bool
    {
        $resourceClass = $context->resourceClass();

        if (!method_exists($resourceClass, 'cloneableFields')) {
            return false;
        }

        return count($resourceClass::cloneableFields()) > 0;
    }

    public function handle(ActionContext $context, Request $request): mixed
    {
        $model = $context->model();
        if (!$model) {
            return null;
        }

        $resourceClass = $context->resourceClass();
        $fields = $resourceClass::cloneableFields();

        if (empty($fields)) {
            return null;
        }

        /** @var class-string<Model>|null $modelClass */
        $modelClass = $resourceClass::$model ?? null;

        if (!$modelClass) {
            return null;
        }

        $attributes = $this->extractAttributes($model, $fields);
        $attributes = $this->cleanupAttributes($model, $attributes);
        $attributes = $resourceClass::mutateCloneAttributes($model, $attributes);

        /** @var Model $clone */
        $clone = $modelClass::create($attributes);

        $resourceClass::afterClone($model, $clone);

        return $clone;
    }

    protected function extractAttributes(Model $model, array $fields): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$field] = $model->getAttribute($field);
        }

        return $attributes;
    }

    protected function cleanupAttributes(Model $model, array $attributes): array
    {
        $guardedKeys = array_filter([
            $model->getKeyName(),
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        return Arr::except($attributes, $guardedKeys);
    }
}
