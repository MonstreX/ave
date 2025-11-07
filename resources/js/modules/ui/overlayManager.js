/**
 * Overlay Manager
 *
 * Manages modal/overlay visibility with a reference counter.
 * Ensures overlay is shown when ANY modal is open and hidden only when ALL modals are closed.
 *
 * Problem solved:
 * - Multiple simultaneous overlays being added/removed quickly
 * - Overlay disappearing prematurely when one modal closes while others are open
 * - Need for centralized overlay state management
 *
 * Solution:
 * - Reference counter tracks how many overlays are currently active
 * - Overlay class only added when count > 0
 * - Overlay class only removed when count reaches 0
 */

let overlayRefCount = 0;
const overlayClass = 'fieldset-editing';

/**
 * Show overlay with reference counting
 * @param {string} [reference='default'] - Identifier for this overlay activation
 * @returns {number} Current reference count
 */
export function showOverlay(reference = 'default') {
    overlayRefCount++;

    // Only add class when transitioning from 0 to 1
    if (overlayRefCount === 1) {
        document.body.classList.add(overlayClass);
    }

    return overlayRefCount;
}

/**
 * Hide overlay with reference counting
 * @param {string} [reference='default'] - Identifier for this overlay deactivation
 * @returns {number} Current reference count
 */
export function hideOverlay(reference = 'default') {
    // Prevent going below 0
    if (overlayRefCount > 0) {
        overlayRefCount--;
    }

    // Only remove class when count reaches 0
    if (overlayRefCount === 0) {
        document.body.classList.remove(overlayClass);
    }

    return overlayRefCount;
}

/**
 * Check if overlay is currently visible
 * @returns {boolean}
 */
export function isOverlayVisible() {
    return overlayRefCount > 0;
}

/**
 * Get current overlay reference count
 * @returns {number}
 */
export function getOverlayRefCount() {
    return overlayRefCount;
}

/**
 * Reset overlay state (for testing or complete cleanup)
 * Removes overlay class and resets counter to 0
 */
export function resetOverlay() {
    overlayRefCount = 0;
    document.body.classList.remove(overlayClass);
}
