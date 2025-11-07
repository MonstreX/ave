/**
 * Form Validation Handler
 *
 * Handles HTML5 validation for forms with hidden/collapsed fields.
 * Temporarily disables validation attributes on hidden fields before form submission
 * to avoid browser blocking the submit action.
 *
 * Server-side validation in Laravel still checks all fields regardless of visibility.
 */

/**
 * Check if an element is visible in the viewport
 * @param {HTMLElement} element
 * @returns {boolean}
 */
function isElementVisible(element) {
    return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
}

/**
 * Temporarily disable validation attributes on hidden fields
 * This allows form submission even when hidden fields have validation errors
 *
 * @param {HTMLElement} field
 */
function disableValidationForField(field) {
    // Store original validation attributes
    if (field.hasAttribute('required')) {
        field.dataset.hadRequired = 'true';
        field.removeAttribute('required');
    }
    if (field.hasAttribute('minlength')) {
        field.dataset.hadMinlength = field.getAttribute('minlength');
        field.removeAttribute('minlength');
    }
    if (field.hasAttribute('maxlength')) {
        field.dataset.hadMaxlength = field.getAttribute('maxlength');
        field.removeAttribute('maxlength');
    }
    if (field.hasAttribute('pattern')) {
        field.dataset.hadPattern = field.getAttribute('pattern');
        field.removeAttribute('pattern');
    }
    if (field.hasAttribute('min')) {
        field.dataset.hadMin = field.getAttribute('min');
        field.removeAttribute('min');
    }
    if (field.hasAttribute('max')) {
        field.dataset.hadMax = field.getAttribute('max');
        field.removeAttribute('max');
    }
    if (field.hasAttribute('step')) {
        field.dataset.hadStep = field.getAttribute('step');
        field.removeAttribute('step');
    }
}

/**
 * Restore validation attributes on a field
 * @param {HTMLElement} field
 */
function restoreValidationForField(field) {
    if (field.dataset.hadRequired === 'true') {
        field.setAttribute('required', '');
        delete field.dataset.hadRequired;
    }
    if (field.dataset.hadMinlength) {
        field.setAttribute('minlength', field.dataset.hadMinlength);
        delete field.dataset.hadMinlength;
    }
    if (field.dataset.hadMaxlength) {
        field.setAttribute('maxlength', field.dataset.hadMaxlength);
        delete field.dataset.hadMaxlength;
    }
    if (field.dataset.hadPattern) {
        field.setAttribute('pattern', field.dataset.hadPattern);
        delete field.dataset.hadPattern;
    }
    if (field.dataset.hadMin) {
        field.setAttribute('min', field.dataset.hadMin);
        delete field.dataset.hadMin;
    }
    if (field.dataset.hadMax) {
        field.setAttribute('max', field.dataset.hadMax);
        delete field.dataset.hadMax;
    }
    if (field.dataset.hadStep) {
        field.setAttribute('step', field.dataset.hadStep);
        delete field.dataset.hadStep;
    }
}

/**
 * Initialize form validation handler
 * Automatically disables validation for hidden fields
 *
 * Strategy: Remove validation attributes from initially hidden fields.
 * Use MutationObserver to handle dynamically hidden/shown fields.
 */
export default function initFormValidation() {
    // Immediately disable validation for any currently hidden fields
    disableValidationForHiddenFields();

    // Watch for changes in visibility (fields being hidden/shown)
    const observer = new MutationObserver(() => {
        disableValidationForHiddenFields();
    });

    // Observe the entire document for changes
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['style', 'class'],
        subtree: true,
        characterData: false,
    });

    // Also watch for form submissions to re-disable in case something re-added validation
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form && form.tagName === 'FORM') {
            disableValidationForHiddenFields(form);
        }
    });
}

/**
 * Find all hidden fields and disable their validation attributes
 * @param {HTMLElement} context - Optional form or container to search within
 */
function disableValidationForHiddenFields(context = document) {
    const allFields = context.querySelectorAll(
        'input[required], input[minlength], input[maxlength], input[pattern], input[min], input[max], input[step], ' +
        'textarea[required], textarea[minlength], textarea[maxlength], textarea[pattern], ' +
        'select[required]'
    );

    Array.from(allFields).forEach(field => {
        if (!isElementVisible(field)) {
            disableValidationForField(field);
        }
    });
}
