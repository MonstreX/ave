<?php

namespace Monstrex\Ave\Core\Actions;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\RowAction;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CloneRecordAction extends BaseAction implements RowAction
{
    protected string $key = 'clone';
    protected ?string $icon = 'voyager-documentation';
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

        /** @var Model $clone */
        $clone = $modelClass::create($attributes);

        $resourceClass::afterClone($model, $clone);

        return $clone;
    }

    protected function extractAttributes(Model $model, array $definition): array
    {
        $attributes = [];

        foreach ($definition as $key => $config) {
            if (is_int($key)) {
                $field = $config;
                $suffix = null;
            } else {
                $field = $key;
                $suffix = $config;
            }

            $value = $model->getAttribute($field);
            if ($suffix !== null) {
                $value = $this->applySuffix($model, $field, $value, (string) $suffix);
            }

            $attributes[$field] = $value;
        }

        return $attributes;
    }

    protected function applySuffix(Model $model, string $field, mixed $value, string $suffix): string
    {
        $base = trim((string) ($value ?? ''));
        $candidate = $base . $suffix;

        if ($candidate === '') {
            $candidate = $suffix;
        }

        return $this->ensureUniqueValue($model, $field, $candidate);
    }

    protected function ensureUniqueValue(Model $model, string $field, string $value): string
    {
        $query = $model->newQuery()->withoutGlobalScopes()->where($field, $value);

        if (!$query->exists()) {
            return $value;
        }

        $counter = 1;
        $base = $value;

        do {
            $candidate = $base . '-' . $counter;
            $counter++;
        } while ($model->newQuery()->withoutGlobalScopes()->where($field, $candidate)->exists());

        return $candidate;
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
