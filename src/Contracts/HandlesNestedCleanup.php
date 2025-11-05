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
     * Returns an array of cleanup action descriptors that will be executed when
     * the parent item is being deleted. Actions are executed on the frontend via fetch requests.
     *
     * Each action should be an array with:
     * - 'url': string - Endpoint URL to call for cleanup
     * - 'method': string - HTTP method (usually 'DELETE')
     * - 'headers': array - Additional headers (e.g., CSRF token)
     * - 'body': array - Request body parameters
     *
     * @param mixed $value - The field value
     * @param array $itemData - Full item data (all fields in the item)
     * @param FormContext|null $context - Form context with model and request
     * @return array - Array of cleanup action descriptors
     */
    public function getNestedCleanupActions(mixed $value, array $itemData, ?FormContext $context = null): array;
}
