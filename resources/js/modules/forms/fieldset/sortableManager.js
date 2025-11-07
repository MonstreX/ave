/**
 * Sortable Instance Manager
 *
 * Centralizes management of Sortable.js instances for Fieldset containers.
 * Uses a WeakMap internally for better memory management and encapsulation.
 * Prevents accidental global pollution and allows automatic cleanup when containers are removed from DOM.
 */

// Store sortable instances associated with their containers
// Using WeakMap ensures automatic cleanup when containers are garbage collected
const containerToSortableMap = new WeakMap();
// Also maintain a Map by fieldName for quick lookup
const fieldNameToContainerMap = new Map();

/**
 * Register a sortable instance for a fieldset container
 * @param {HTMLElement} container - The fieldset container element
 * @param {string} fieldName - The field name
 * @param {Sortable} instance - The Sortable.js instance
 */
export function registerSortable(container, fieldName, instance) {
    if (!container) {
        console.error('Cannot register sortable: container is null');
        return;
    }

    containerToSortableMap.set(container, instance);
    fieldNameToContainerMap.set(fieldName, container);
}

/**
 * Get a sortable instance by field name
 * @param {string} fieldName - The field name
 * @returns {Sortable|null} The Sortable instance or null if not found
 */
export function getSortable(fieldName) {
    const container = fieldNameToContainerMap.get(fieldName);
    if (!container) {
        return null;
    }

    // Verify container is still in DOM and has the instance
    if (!document.contains(container)) {
        // Container has been removed, clean up
        fieldNameToContainerMap.delete(fieldName);
        return null;
    }

    return containerToSortableMap.get(container) || null;
}

/**
 * Get a sortable instance by container element
 * @param {HTMLElement} container - The fieldset container element
 * @returns {Sortable|null} The Sortable instance or null if not found
 */
export function getSortableByContainer(container) {
    return containerToSortableMap.get(container) || null;
}

/**
 * Unregister a sortable instance
 * @param {string} fieldName - The field name
 */
export function unregisterSortable(fieldName) {
    fieldNameToContainerMap.delete(fieldName);
}

/**
 * Check if a sortable instance exists
 * @param {string} fieldName - The field name
 * @returns {boolean}
 */
export function hasSortable(fieldName) {
    const container = fieldNameToContainerMap.get(fieldName);
    if (!container || !document.contains(container)) {
        return false;
    }
    return containerToSortableMap.has(container);
}
