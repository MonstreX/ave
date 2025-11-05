<?php

namespace Monstrex\Ave\Contracts;

use Monstrex\Ave\Core\FormContext;

/**
 * HandlesNestedCleanup - For fields that need cleanup when parent item is deleted
 *
 * This contract is used for fields that have external resources (like media collections)
 * that need to be cleaned up when the containing item (e.g., Fieldset item) is deleted.
 *
 * Example: Media field in a Fieldset needs to delete its media collection when item is removed.
 */
interface HandlesNestedCleanup
{
    /**
     * Get cleanup actions needed for this field when nested item is deleted
     *
     * Returns an array of closures that will be executed as deferred actions after
     * the model is saved. Each closure receives the parent model as argument.
     *
     * The field is responsible for implementing its own cleanup logic without exposing
     * internal details to RequestProcessor (encapsulation).
     *
     * @param mixed $value - The field value
     * @param array $itemData - Full item data (all fields in the item)
     * @param FormContext|null $context - Form context with model and request
     * @return array<\Closure> - Array of cleanup action closures
     */
    public function getNestedCleanupActions(mixed $value, array $itemData, ?FormContext $context = null): array;
}
