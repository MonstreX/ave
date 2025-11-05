<?php

namespace Monstrex\Ave\Core\Fields\Fieldset;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monstrex\Ave\Contracts\HandlesPersistence;
use Monstrex\Ave\Contracts\HandlesNestedCleanup;
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

    public function process(Request $request, FormContext $context, mixed $originalValue = null): ProcessResult
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

        // Process cleanup for deleted items
        $cleanupActions = $this->collectCleanupActionsForDeletedItems($originalValue, $context);
        $deferred = array_merge($deferred, $cleanupActions);

        Log::debug('Fieldset cleanup actions collected', [
            'fieldset' => $this->fieldset->getKey(),
            'cleanup_actions_count' => count($cleanupActions),
            'total_deferred_actions' => count($deferred),
        ]);

        return new ProcessResult($processedItems, $deferred);
    }

    /**
     * Collect cleanup actions for items that were deleted (present in original but not in new)
     *
     * @param mixed $originalValue - The original data from database
     * @param FormContext $context - The form context with record
     * @return array - Array of deferred action closures
     */
    private function collectCleanupActionsForDeletedItems(mixed $originalValue, FormContext $context): array
    {
        if (!is_array($originalValue) || empty($originalValue)) {
            Log::debug('No original fieldset value, skipping cleanup', [
                'fieldset' => $this->fieldset->getKey(),
            ]);
            return [];
        }

        // Normalize original value
        $originalItems = [];
        foreach ($originalValue as $item) {
            if (is_array($item) && isset($item['_id'])) {
                $originalItems[(int) $item['_id']] = $item;
            }
        }

        // Get current item IDs from request
        $currentIds = new \stdClass();
        $currentIdsList = $this->collectCurrentItemIds();
        foreach ($currentIdsList as $id) {
            $currentIds->{$id} = true;
        }

        Log::debug('Fieldset item comparison', [
            'fieldset' => $this->fieldset->getKey(),
            'original_item_ids' => array_keys($originalItems),
            'current_item_ids' => $currentIdsList,
        ]);

        $deferredActions = [];

        // For each deleted item, collect cleanup actions from its fields
        foreach ($originalItems as $itemId => $itemData) {
            if (isset($currentIds->{$itemId})) {
                // Item still exists, skip
                continue;
            }

            Log::debug('Fieldset item deleted, collecting cleanup actions', [
                'fieldset' => $this->fieldset->getKey(),
                'item_id' => $itemId,
            ]);

            // Item was deleted - collect cleanup actions
            $itemCleanupActions = $this->collectCleanupActionsForItem($itemId, $itemData, $context);
            $deferredActions = array_merge($deferredActions, $itemCleanupActions);

            Log::debug('Cleanup actions collected for deleted item', [
                'fieldset' => $this->fieldset->getKey(),
                'item_id' => $itemId,
                'action_count' => count($itemCleanupActions),
            ]);
        }

        return $deferredActions;
    }

    /**
     * Collect cleanup actions for a single deleted item
     *
     * @param int $itemId - The item ID
     * @param array $itemData - The item data from database
     * @param FormContext $context - The form context
     * @return array - Array of deferred action closures
     */
    private function collectCleanupActionsForItem(int $itemId, array $itemData, FormContext $context): array
    {
        $deferredActions = [];

        // Build the state path for this item
        $itemStatePath = $this->fieldset->getItemStatePath($itemId);

        foreach ($this->fieldset->getChildSchema() as $schemaField) {
            if (!$schemaField instanceof AbstractField) {
                continue;
            }

            // Set state path and context for the field
            $childStatePath = "{$itemStatePath}.{$schemaField->baseKey()}";
            $nestedField = $schemaField
                ->statePath($childStatePath)
                ->container($this->fieldset)
                ->nestWithin($this->fieldset->getKey(), $itemId);

            // Check if field handles cleanup
            if (!$nestedField instanceof HandlesNestedCleanup) {
                continue;
            }

            $baseKey = $schemaField->baseKey();
            $fieldValue = $itemData[$baseKey] ?? null;

            // Get cleanup actions for this field
            // Actions are already closures ready to be executed
            $actions = $nestedField->getNestedCleanupActions($fieldValue, $itemData, $context);

            // Add closures directly to deferred actions
            $deferredActions = array_merge($deferredActions, $actions);
        }

        return $deferredActions;
    }

    /**
     * Collect all item IDs from current request
     *
     * @return array<int>
     */
    private function collectCurrentItemIds(): array
    {
        $rawItems = \request()->input($this->fieldset->getKey(), []);

        if (!is_array($rawItems)) {
            return [];
        }

        $ids = [];
        foreach ($rawItems as $item) {
            if (is_array($item) && isset($item['_id'])) {
                $id = (int) $item['_id'];
                if (!in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
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
