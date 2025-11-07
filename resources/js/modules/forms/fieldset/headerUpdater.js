/**
 * Fieldset Item Header Updater
 *
 * Provides unified logic for updating Fieldset item headers (title and preview).
 * Used by both default and cards display modes.
 */

/**
 * Update a fieldset item's header (title and preview)
 *
 * @param {HTMLElement} item - The fieldset item element
 * @param {string|null} titleFieldName - Name of the field to use for title
 * @param {string|null} previewFieldName - Name of the field to use for preview
 * @returns {void}
 */
export function updateItemHeader(item, titleFieldName = null, previewFieldName = null) {
    if (!item) return;

    // If fieldNames not provided, try to get them from the item's header attributes
    if (!titleFieldName || !previewFieldName) {
        const header = item.querySelector('.fieldset-item-header, [data-head-title-field], [data-head-preview-field]');
        if (!header) return;

        titleFieldName = titleFieldName || header.dataset.headTitleField;
        previewFieldName = previewFieldName || header.dataset.headPreviewField;
    }

    // Update title
    if (titleFieldName) {
        updateItemTitle(item, titleFieldName);
    }

    // Update preview
    if (previewFieldName) {
        updateItemPreview(item, previewFieldName);
    }
}

/**
 * Update the title of a fieldset item
 *
 * @param {HTMLElement} item - The fieldset item element
 * @param {string} fieldName - Name of the field containing the title
 * @returns {void}
 */
export function updateItemTitle(item, fieldName) {
    if (!item || !fieldName) return;

    const titleElement = item.querySelector('[data-item-title]');
    if (!titleElement) return;

    // Try both selectors: name$= (ends with) and name*= (contains)
    // This handles both traditional and nested field naming
    let input = item.querySelector(`[name$="[${fieldName}]"]`);
    if (!input) {
        input = item.querySelector(`[name*="[${fieldName}]"]`);
    }

    if (input && input.value) {
        titleElement.textContent = input.value;
        titleElement.style.display = 'block';
    } else {
        titleElement.style.display = 'none';
    }

    // Update placeholder visibility
    updatePlaceholder(item);
}

/**
 * Update placeholder visibility based on card content
 * @param {HTMLElement} item - The fieldset item element
 */
function updatePlaceholder(item) {
    const placeholder = item.querySelector('[data-item-placeholder]');
    if (!placeholder) return;

    const titleElement = item.querySelector('[data-item-title]');
    const previewElement = item.querySelector('[data-item-preview]');

    // Show placeholder if both title and preview are empty/hidden
    const hasTitle = titleElement && titleElement.style.display !== 'none' && titleElement.textContent.trim();
    const hasPreview = previewElement && previewElement.style.display !== 'none' && previewElement.style.backgroundImage;

    if (!hasTitle && !hasPreview) {
        placeholder.style.display = 'flex';
    } else {
        placeholder.style.display = 'none';
    }
}

/**
 * Update the preview (image) of a fieldset item
 *
 * Supports two approaches:
 * 1. Using data-preview-url attribute (preferred - no encapsulation violation)
 * 2. Fallback to .media-preview img selector (for compatibility)
 *
 * @param {HTMLElement} item - The fieldset item element
 * @param {string} fieldName - Name of the media field containing preview
 * @returns {void}
 */
export function updateItemPreview(item, fieldName) {
    if (!item || !fieldName) return;

    const previewElement = item.querySelector('[data-item-preview]');
    if (!previewElement) return;

    // Find the media field container
    const mediaContainer = item.querySelector(`[data-field-name*="${fieldName}"]`);
    if (!mediaContainer) {
        previewElement.style.backgroundImage = '';
        previewElement.style.display = 'none';
        return;
    }

    let previewUrl = null;

    // Preferred: Use data-preview-url attribute (new, no encapsulation violation)
    previewUrl = mediaContainer.dataset.previewUrl;

    // Fallback: Find image in .media-preview (legacy, for backward compatibility)
    if (!previewUrl) {
        const firstImg = mediaContainer.querySelector('.media-preview img');
        if (firstImg && firstImg.src) {
            previewUrl = firstImg.src;
        }
    }

    // Apply preview
    if (previewUrl && previewUrl.length > 0) {
        previewElement.style.backgroundImage = `url('${previewUrl}')`;
        previewElement.style.display = 'block';
    } else {
        previewElement.style.backgroundImage = '';
        previewElement.style.display = 'none';
    }

    // Update placeholder visibility
    updatePlaceholder(item);
}

/**
 * Update all headers in a container
 *
 * @param {HTMLElement} container - The fieldset container element
 * @returns {void}
 */
export function updateAllItemHeaders(container) {
    if (!container) return;

    container.querySelectorAll('[data-item-index], .fieldset-item').forEach(item => {
        updateItemHeader(item);
    });
}
