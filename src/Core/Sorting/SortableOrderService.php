<?php

namespace Monstrex\Ave\Core\Sorting;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Monstrex\Ave\Core\Resource;
use Monstrex\Ave\Core\Table;
use Monstrex\Ave\Exceptions\ResourceException;

class SortableOrderService
{
    public function __construct(
        protected ConnectionInterface $connection
    ) {
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function reorder(
        array $order,
        string $orderColumn,
        Resource $resource,
        ?Authenticatable $user,
        string $modelClass,
        string $slug
    ): void {
        if (empty($order)) {
            return;
        }

        $model = new $modelClass();
        $primaryKey = $model->getKeyName();

        $this->connection->transaction(function () use ($order, $orderColumn, $resource, $user, $modelClass, $slug, $primaryKey) {
            $ids = array_map('intval', array_keys($order));
            $models = $modelClass::query()
                ->whereKey($ids)
                ->lockForUpdate()
                ->get()
                ->keyBy($primaryKey);

            $existingIds = $models->keys()->map(fn ($key) => (int) $key)->all();
            $missing = array_diff($ids, $existingIds);
            if (!empty($missing)) {
                throw ResourceException::modelNotFound($slug, implode(',', $missing));
            }

            $payload = [];
            foreach ($order as $id => $position) {
                $id = (int) $id;
                /** @var Model|null $model */
                $model = $models->get($id);

                if (!$resource->can('update', $user, $model)) {
                    throw ResourceException::unauthorized($slug, 'update');
                }

                if ((int) $model->getAttribute($orderColumn) === (int) $position) {
                    continue;
                }

                $model->setAttribute($orderColumn, (int) $position);
                $payload[] = $model->getAttributes();
            }

            $this->chunkedUpsert($modelClass, $payload, $primaryKey, [$orderColumn]);
        });
    }

    /**
     * @param  array<int,array<string,mixed>>  $treePayload
     * @param  class-string<Model>  $modelClass
     */
    public function rebuildTree(
        array $treePayload,
        Table $table,
        Resource $resource,
        ?Authenticatable $user,
        string $modelClass,
        string $slug
    ): void {
        if (empty($treePayload)) {
            return;
        }

        $parentColumn = $table->getParentColumn() ?? 'parent_id';
        $orderColumn = $table->getOrderColumn() ?? 'order';
        $maxDepth = max(1, $table->getTreeMaxDepth());

        $model = new $modelClass();
        $primaryKey = $model->getKeyName();

        $assignments = [];
        $this->flattenTree(
            $treePayload,
            null,
            $parentColumn,
            $orderColumn,
            $primaryKey,
            1,
            $maxDepth,
            $assignments,
            $slug
        );

        if (empty($assignments)) {
            return;
        }

        $this->connection->transaction(function () use ($assignments, $resource, $user, $modelClass, $slug, $primaryKey, $parentColumn, $orderColumn) {
            $ids = array_map('intval', array_column($assignments, $primaryKey));

            $models = $modelClass::query()
                ->whereKey($ids)
                ->lockForUpdate()
                ->get()
                ->keyBy($primaryKey);

            $existingIds = $models->keys()->map(fn ($key) => (int) $key)->all();
            $missing = array_diff($ids, $existingIds);
            if (!empty($missing)) {
                throw ResourceException::modelNotFound($slug, implode(',', $missing));
            }

            $payload = [];
            foreach ($assignments as $assignment) {
                /** @var Model|null $model */
                $model = $models->get($assignment[$primaryKey]);

                if (!$resource->can('update', $user, $model)) {
                    throw ResourceException::unauthorized($slug, 'update');
                }

                $model->setAttribute($parentColumn, $assignment[$parentColumn]);
                $model->setAttribute($orderColumn, $assignment[$orderColumn]);
                $payload[] = $model->getAttributes();
            }

            $this->chunkedUpsert(
                $modelClass,
                $payload,
                $primaryKey,
                [$parentColumn, $orderColumn]
            );
        });
    }

    /**
     * @param  array<int,array<string,mixed>>  $payload
     * @param  class-string<Model>  $modelClass
     * @param  array<int,string>  $columns
     */
    protected function chunkedUpsert(
        string $modelClass,
        array $payload,
        string $primaryKey,
        array $columns
    ): void {
        if (empty($payload)) {
            return;
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            $modelClass::query()->upsert($chunk, [$primaryKey], $columns);
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $items
     * @param  array<int,array<string,mixed>>  $assignments
     */
    protected function flattenTree(
        array $items,
        ?int $parentId,
        string $parentColumn,
        string $orderColumn,
        string $primaryKey,
        int $currentDepth,
        int $maxDepth,
        array &$assignments,
        string $slug
    ): void {
        if ($currentDepth > $maxDepth) {
            throw new ResourceException("Tree depth exceeds allowed maximum for resource '{$slug}'", 422);
        }

        foreach ($items as $index => $item) {
            if (!isset($item['id'])) {
                throw new ResourceException('Tree payload is missing node id', 422);
            }

            $id = (int) $item['id'];

            $assignments[] = [
                $primaryKey => $id,
                $parentColumn => $parentId,
                $orderColumn => $index,
            ];

            if (isset($item['children'])) {
                $children = $item['children'];
                if (!is_array($children)) {
                    throw new ResourceException('Tree node children must be an array', 422);
                }

                $this->flattenTree(
                    $children,
                    $id,
                    $parentColumn,
                    $orderColumn,
                    $primaryKey,
                    $currentDepth + 1,
                    $maxDepth,
                    $assignments,
                    $slug
                );
            }
        }
    }
}
