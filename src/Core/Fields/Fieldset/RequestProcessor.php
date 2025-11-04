<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        foreach ($rawItems as $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            $itemId = $this->resolveItemId($itemData);
            $normalizedItem = ['_id' => $itemId];
            $hasMeaningfulData = false;

            foreach ($this->fieldset->getChildSchema() as $schemaField) {
                if (!$schemaField instanceof AbstractField) {
                    continue;
                }

                $nestedField = $schemaField->nestWithin($this->fieldset->getKey(), $itemId);
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
        }

        return new ProcessResult($processedItems, $deferred);
    }

    private function resolveItemId(array &$item): string
    {
        $identifier = $item['_id'] ?? null;

        if (is_string($identifier) && $identifier !== '') {
            return $identifier;
        }

        if (is_numeric($identifier)) {
            $identifier = (string) $identifier;
            $item['_id'] = $identifier;

            return $identifier;
        }

        $generated = Str::lower(Str::ulid()->toBase32());
        $identifier = substr($generated, 0, 12);
        $item['_id'] = $identifier;

        return $identifier;
    }
}
