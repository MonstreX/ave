<?php

namespace Monstrex\Ave\Services;

use Monstrex\Ave\Models\Media as MediaModel;

/**
 * MediaManagementService - Handles media management operations (delete, reorder, update properties, etc)
 */
class MediaManagementService
{
    /**
     * Delete media by ID
     */
    public function deleteMedia(int $id): void
    {
        $media = MediaModel::findOrFail($id);
        $media->delete();
    }

    /**
     * Bulk delete media by IDs
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = 0;

        // Delete each media item
        foreach ($ids as $id) {
            try {
                $media = MediaModel::find($id);
                if ($media) {
                    $media->delete();
                    $deleted++;
                }
            } catch (\Exception $e) {
                \Log::error('[MediaManagementService.bulkDelete] Failed to delete media', [
                    'media_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with next item
            }
        }

        return $deleted;
    }

    /**
     * Delete entire media collection (used by Fieldset item removal)
     */
    public function destroyCollection(string $modelType, int $modelId, string $collection): int
    {
        if (!class_exists($modelType)) {
            throw new \Exception('Model class not found: ' . $modelType);
        }

        $deleted = 0;

        MediaModel::query()
            ->where('collection_name', $collection)
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->chunkById(100, static function ($items) use (&$deleted) {
                foreach ($items as $media) {
                    $media->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    /**
     * Reorder media items
     */
    public function reorder(array $mediaItems): int
    {
        $updated = 0;

        foreach ($mediaItems as $item) {
            $media = MediaModel::find($item['id'] ?? null);
            if ($media) {
                $media->order = $item['order'] ?? 0;
                $media->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Update media properties
     */
    public function updateProperties(int $id, array $props): MediaModel
    {
        $media = MediaModel::findOrFail($id);

        if (!empty($props)) {
            $currentProps = $media->props ? json_decode($media->props, true) : [];
            $media->props = json_encode(array_merge($currentProps, $props));
            $media->save();
        }

        return $media;
    }
}
