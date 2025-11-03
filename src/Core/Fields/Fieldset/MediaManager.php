<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Monstrex\Ave\Core\Fields\Media;
use Monstrex\Ave\Models\Media as MediaModel;

/**
 * Handles media payload parsing and deferred execution for Fieldset items.
 */
class MediaManager
{
    public function collectOperation(
        Request $request,
        string $fieldsetKey,
        Media $field,
        int $index,
        int $itemId,
    ): MediaOperation {
        $fieldName = $field->getKey();
        $base = "{$fieldsetKey}[{$index}][{$fieldName}]";
        $collectionName = "{$fieldsetKey}_{$itemId}_{$fieldName}";
        $metaKey = $this->buildMetaKey($base);

        $rawUploaded = Arr::get($request->input('__media_uploaded', []), $metaKey, []);
        $rawDeleted = Arr::get($request->input('__media_deleted', []), $metaKey, []);
        $rawOrder = Arr::get($request->input('__media_order', []), $metaKey, []);
        $rawProps = Arr::get($request->input('__media_props', []), $metaKey, []);

        $uploaded = $this->parseIdList($rawUploaded);
        $deleted = $this->parseIdList($rawDeleted);
        $order = $this->parseIdList($rawOrder);
        $props = $this->normalisePropsInput($rawProps);

        logger()->debug('[Fieldset][MediaManager] collectOperation', [
            'fieldset' => $fieldsetKey,
            'field' => $fieldName,
            'index' => $index,
            'item_id' => $itemId,
            'meta_key' => $metaKey,
            'raw_uploaded' => $rawUploaded,
            'raw_deleted' => $rawDeleted,
            'raw_order' => $rawOrder,
            'raw_props' => $rawProps,
            'parsed_uploaded' => $uploaded,
            'parsed_deleted' => $deleted,
            'parsed_order' => $order,
            'parsed_props' => $props,
        ]);

        return new MediaOperation(
            $collectionName,
            $uploaded,
            $deleted,
            $order,
            $props,
        );
    }

    public function makeDeferredAction(MediaOperation $operation): Closure
    {
        return static function (Model $record) use ($operation): void {
            if (!empty($operation->deleted)) {
                $record->media()
                    ->where('collection_name', $operation->collection)
                    ->whereIn('id', $operation->deleted)
                    ->delete();
            }

            if (!empty($operation->uploaded)) {
                MediaModel::whereIn('id', $operation->uploaded)->update([
                    'model_type' => get_class($record),
                    'model_id' => $record->getKey(),
                    'collection_name' => $operation->collection,
                ]);
            }

            if (!empty($operation->order)) {
                $mediaItems = MediaModel::where('model_type', get_class($record))
                    ->where('model_id', $record->getKey())
                    ->where('collection_name', $operation->collection)
                    ->whereIn('id', $operation->order)
                    ->get()
                    ->keyBy('id');

                foreach ($operation->order as $index => $mediaId) {
                    $media = $mediaItems->get($mediaId);
                    if ($media) {
                        $media->order = $index;
                        $media->save();
                    }
                }
            }

            if (!empty($operation->props)) {
                $mediaItems = MediaModel::where('model_type', get_class($record))
                    ->where('model_id', $record->getKey())
                    ->where('collection_name', $operation->collection)
                    ->whereIn('id', array_keys($operation->props))
                    ->get()
                    ->keyBy('id');

                foreach ($operation->props as $mediaId => $values) {
                    $media = $mediaItems->get($mediaId);
                    if (!$media) {
                        continue;
                    }

                    $currentProps = json_decode($media->props, true) ?? [];
                    $media->props = json_encode(array_merge($currentProps, $values));
                    $media->save();
                }
            }
        };
    }

    public function calculateRemainingMedia(?Model $record, MediaOperation $operation): int
    {
        $existing = 0;

        if ($record && $record->exists) {
            $existing = MediaModel::query()
                ->where('model_type', get_class($record))
                ->where('model_id', $record->getKey())
                ->where('collection_name', $operation->collection)
                ->count();
        }

        $existingAfterDeletion = max(0, $existing - count($operation->deleted));

        return $existingAfterDeletion + count($operation->uploaded);
    }

    /**
     * @return array<int,int>
     */
    private function parseIdList(mixed $value): array
    {
        if (is_string($value)) {
            if (trim($value) === '') {
                return [];
            }

            $value = array_map('trim', explode(',', $value));
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $entry) {
            if ($entry === null || $entry === '') {
                continue;
            }
            $ids[] = (int) $entry;
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function normalisePropsInput(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $result = [];
        foreach ($input as $key => $value) {
            $id = (int) $key;
            if ($id <= 0) {
                continue;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : [];
            }

            if (!is_array($value)) {
                continue;
            }

            $result[$id] = $value;
        }

        return $result;
    }

    private function buildMetaKey(string $key): string
    {
        $metaKey = str_replace(['[', ']'], '_', $key);
        $metaKey = preg_replace('/_+/', '_', $metaKey);

        return trim($metaKey, '_');
    }
}
