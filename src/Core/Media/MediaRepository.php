<?php

namespace Monstrex\Ave\Core\Media;

use Illuminate\Database\Eloquent\Model;

/**
 * Central service for manipulating media records associated with models.
 */
class MediaRepository
{
    public function __construct(
        private string $mediaModelClass,
    ) {
    }

    public function delete(Model $model, string $collection, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $this->mediaModelClass::query()
            ->where('collection_name', $collection)
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->whereIn('id', $ids)
            ->delete();
    }

    public function attach(Model $model, string $collection, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $this->mediaModelClass::query()
            ->whereIn('id', $ids)
            ->update([
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'collection_name' => $collection,
            ]);
    }

    public function reorder(Model $model, string $collection, array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        $mediaItems = $this->mediaModelClass::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        foreach ($orderedIds as $index => $mediaId) {
            $media = $mediaItems->get($mediaId);
            if (!$media) {
                continue;
            }

            $media->order = $index;
            $media->save();
        }
    }

    public function updateProps(Model $model, string $collection, array $props): void
    {
        if (empty($props)) {
            return;
        }

        $mediaItems = $this->mediaModelClass::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->whereIn('id', array_keys($props))
            ->get()
            ->keyBy('id');

        foreach ($props as $mediaId => $values) {
            $media = $mediaItems->get($mediaId);
            if (!$media) {
                continue;
            }

            $currentProps = json_decode($media->props, true) ?? [];
            $media->props = json_encode(array_merge($currentProps, $values));
            $media->save();
        }
    }

    public function count(Model $model, string $collection): int
    {
        return $this->mediaModelClass::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->count();
    }

    public function allForCollection(?Model $model, string $collection): array
    {
        if (!$model || !$model->exists) {
            return [];
        }

        $items = $this->mediaModelClass::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->where('collection_name', $collection)
            ->orderBy('order')
            ->get();

        return $items->map(fn ($media) => $this->mapMedia($media))->keyBy('id')->all();
    }

    public function infoForIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $items = $this->mediaModelClass::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $items->map(fn ($media) => $this->mapMedia($media))->all();
    }

    /**
     * @return array<string,mixed>
     */
    protected function mapMedia($media): array
    {
        return [
            'id' => $media->id,
            'collection' => $media->collection_name,
            'disk' => $media->disk,
            'path' => $media->path(),
            'url' => $media->url(),
            'full_url' => $media->fullUrl(),
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'order' => $media->order,
            'props' => json_decode($media->props ?? '[]', true) ?: [],
        ];
    }
}

