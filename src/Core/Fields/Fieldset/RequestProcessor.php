<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Core\FormContext;
use Monstrex\Ave\Core\Fields\AbstractField;
use Monstrex\Ave\Core\Fields\Fieldset as FieldsetField;

/**
 * Normalises incoming request payload for Fieldset fields.
 */
class RequestProcessor
{
    public function __construct(
        private FieldsetField $fieldset,
    ) {
    }

    public function process(Request $request, FormContext $context): ProcessResult
    {
        $rawItems = $request->input($this->fieldset->getKey(), []);

        if (!is_array($rawItems)) {
            $rawItems = [];
        }

        $processedItems = [];
        $deferred = [];
        $usedIds = [];

        foreach ($rawItems as $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            $itemId = $this->resolveItemId($itemData, $usedIds);
            $normalizedItem = ['_id' => $itemId];
            $hasMeaningfulData = false;

            foreach ($this->fieldset->getChildSchema() as $schemaField) {
                if (!$schemaField instanceof AbstractField) {
                    continue;
                }

                // Set state path for this item's field (e.g., 'features.0.icon')
                $itemStatePath = $this->fieldset->getItemStatePath($itemId);
                $childStatePath = "{$itemStatePath}.{$schemaField->baseKey()}";

                $nestedField = $schemaField
                    ->statePath($childStatePath)
                    ->container($this->fieldset)
                    ->nestWithin($this->fieldset->getKey(), $itemId);

                $baseKey = $schemaField->baseKey();
                $rawValue = $itemData[$baseKey] ?? null;

                if ($nestedField instanceof HandlesPersistence) {
                    $result = $nestedField->prepareForSave($rawValue, $request, $context);
                    $normalizedValue = $result->value();
                    $deferred = array_merge($deferred, $result->deferredActions());
                } else {
                    $normalizedValue = $nestedField->extract($rawValue);
                }

                if ($this->fieldset->valueIsMeaningful($normalizedValue)) {
                    $hasMeaningfulData = true;
                }

                $normalizedItem[$baseKey] = $normalizedValue;
            }

            if (!$hasMeaningfulData) {
                Log::debug('Fieldset request item skipped (no meaningful data)', [
                    'fieldset' => $this->fieldset->getKey(),
                    'item_id' => $itemId,
                ]);
                continue;
            }

            Log::debug('Fieldset request item processed', [
                'fieldset' => $this->fieldset->getKey(),
                'item_id' => $itemId,
                'stored_keys' => array_keys($normalizedItem),
            ]);

            $processedItems[] = $normalizedItem;
            $usedIds[$itemId] = true;
        }

        return new ProcessResult($processedItems, $deferred);
    }

    private function resolveItemId(array &$item, array &$usedIds): int
    {
        $identifier = $item['_id'] ?? null;

        if (is_numeric($identifier)) {
            $id = (int) $identifier;
        } elseif (is_string($identifier) && ctype_digit($identifier)) {
            $id = (int) $identifier;
        } else {
            $id = $this->nextAvailableIndex($usedIds);
        }

        $item['_id'] = $id;

        return $id;
    }

    private function nextAvailableIndex(array $usedIds): int
    {
        $candidate = 0;

        while (isset($usedIds[$candidate])) {
            $candidate++;
        }

        return $candidate;
    }
}
