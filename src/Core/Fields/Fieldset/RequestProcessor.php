<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;
use Monstrex\Ave\Core\Fields\Media;

/**
 * Normalises incoming request payload for Fieldset fields.
 */
class RequestProcessor
{
    public function __construct(
        private FieldsetField $fieldset,
        private MediaManager $mediaManager,
    ) {}

    public function process(Request $request, FormContext $context): ProcessResult
    {
        $fieldsetKey = $this->fieldset->getKey();
        $rawItems = $request->input($fieldsetKey, []);

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $normalized = array_values(array_filter($rawItems, 'is_array'));

        $processedItems = [];
        $deferred = [];
        $record = $context->record();

        foreach ($normalized as $index => $itemData) {
            $itemData = is_array($itemData) ? $itemData : [];

            $itemId = isset($itemData['_id']) ? (int) $itemData['_id'] : ($index + 1);
            $itemData['_id'] = $itemId;

            $hasMeaningfulData = false;

            logger()->info('[Fieldset][RequestProcessor] processing item', [
                'fieldset' => $fieldsetKey,
                'index' => $index,
                'item_id' => $itemId,
                'incoming' => $itemData,
            ]);

            foreach ($this->fieldset->getChildSchema() as $schemaField) {
                if (!$schemaField instanceof AbstractField) {
                    continue;
                }

                $fieldName = $schemaField->getKey();

                if ($schemaField instanceof Media) {
                    $operation = $this->mediaManager->collectOperation(
                        $request,
                        $fieldsetKey,
                        $schemaField,
                        $index,
                        $itemId,
                    );

                    if ($operation->hasAny()) {
                        $deferred[] = $this->mediaManager->makeDeferredAction($operation);
                    }

                    $remainingMedia = $this->mediaManager->calculateRemainingMedia($record, $operation);

                    if (!empty($itemData[$fieldName] ?? null)
                        || !empty($operation->uploaded)
                        || !empty($operation->order)
                        || !empty($operation->props)
                        || $remainingMedia > 0
                    ) {
                        $hasMeaningfulData = true;
                    }

                    $itemData[$fieldName] = $operation->collection;

                    $metaKey = str_replace(['[', ']'], '_', "{$fieldsetKey}[{$index}][{$fieldName}]");
                    $metaKey = preg_replace('/_+/', '_', $metaKey);
                    $metaKey = trim($metaKey, '_');

                    logger()->info('[Fieldset][RequestProcessor] media field processed', [
                        'fieldset' => $fieldsetKey,
                        'field' => $fieldName,
                        'index' => $index,
                        'item_id' => $itemId,
                        'meta_key' => $metaKey,
                        'operation_uploaded' => $operation->uploaded,
                        'operation_deleted' => $operation->deleted,
                        'operation_order' => $operation->order,
                        'operation_props' => $operation->props,
                        'remaining_media' => $remainingMedia,
                        'has_meaningful_data' => $hasMeaningfulData,
                        'stored_value' => $itemData[$fieldName],
                    ]);

                    continue;
                }

                if ($fieldName === '_id') {
                    continue;
                }

                $value = $itemData[$fieldName] ?? null;
                if ($this->fieldset->valueIsMeaningful($value)) {
                    $hasMeaningfulData = true;
                }
            }

            if (!$hasMeaningfulData) {
                logger()->info('[Fieldset][RequestProcessor] skipping empty item', [
                    'fieldset' => $fieldsetKey,
                    'index' => $index,
                    'item_id' => $itemId,
                ]);
                continue;
            }

            logger()->info('[Fieldset][RequestProcessor] item accepted', [
                'fieldset' => $fieldsetKey,
                'index' => $index,
                'item_id' => $itemId,
                'prepared_item' => $itemData,
            ]);

            $processedItems[] = $itemData;
        }

        logger()->info('[Fieldset][RequestProcessor] result', [
            'fieldset' => $fieldsetKey,
            'items_count' => count($processedItems),
            'deferred_count' => count($deferred),
        ]);

        return new ProcessResult($processedItems, $deferred);
    }
}
