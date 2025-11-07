/**
 * Initialize FieldSet Cards display variant functionality
 *
 * This module enhances the standard fieldset with card-specific features:
 * - Updates preview images and titles from form fields
 * - Works in conjunction with standard initFieldSet for add/delete/drag functionality
 *
 * Integrates with the standard fieldset data attributes:
 * - [data-fieldset] - main container
 * - [data-item-index] - item index
 * - data-head-title-field - which field contains the title
 * - data-head-preview-field - which field contains the preview image
 */
export default function initFieldsetCards(root = document) {
    // Find all fieldset containers with card view
    root.querySelectorAll('[data-fieldset].fieldset-cards-view').forEach(container => {
        // Only process if has preview or title fields to display
        const hasHeadFields = container.querySelector('[data-head-title-field], [data-head-preview-field]');
        if (!hasHeadFields) {
            return;
        }

        // Initialize preview and title for existing items
        updateAllItemHeaders();

        // Watch for changes to form fields and update headers
        container.addEventListener('input', handleFieldChange, true);
        container.addEventListener('change', handleFieldChange, true);

        // Watch for media field changes
        container.addEventListener('mediaChanged', handleMediaChange, true);

        // Re-initialize headers when DOM updates (after adding new item)
        container.addEventListener('dom:updated', () => {
            updateAllItemHeaders();
        });

        // ===== FUNCTIONS =====

        function updateAllItemHeaders() {
            container.querySelectorAll('[data-item-index]').forEach(item => {
                updateItemHeader(item);
            });
        }

        function updateItemHeader(item) {
            const header = item.querySelector('[data-head-title-field], [data-head-preview-field]');
            if (!header) return;

            const titleFieldName = header.dataset.headTitleField;
            const previewFieldName = header.dataset.headPreviewField;

            // Update title from form field
            if (titleFieldName) {
                updateItemTitle(item, titleFieldName);
            }

            // Update preview image from media field
            if (previewFieldName) {
                updateItemPreview(item, previewFieldName);
            }
        }

        function updateItemTitle(item, fieldName) {
            const titleElement = item.querySelector('[data-item-title]');
            if (!titleElement) return;

            // Find the input field with matching name
            const input = item.querySelector(`[name*="[${fieldName}]"]`);
            if (input && input.value) {
                titleElement.textContent = input.value;
            } else {
                titleElement.textContent = '';
            }
        }

        function updateItemPreview(item, fieldName) {
            const previewElement = item.querySelector('[data-item-preview]');
            if (!previewElement) return;

            // Find the media/image field container
            const mediaContainer = item.querySelector(`[data-field-name*="${fieldName}"]`);
            if (!mediaContainer) return;

            // Look for image inside media field
            const img = mediaContainer.querySelector('img');
            if (img && img.src) {
                previewElement.style.backgroundImage = `url('${img.src}')`;
                previewElement.textContent = ''; // Clear placeholder
            } else {
                previewElement.style.backgroundImage = '';
                previewElement.textContent = ''; // Show CSS placeholder
            }
        }

        function handleFieldChange(e) {
            const item = e.target.closest('[data-item-index]');
            if (item) {
                updateItemHeader(item);
            }
        }

        function handleMediaChange(e) {
            const item = e.target.closest('[data-item-index]');
            if (item) {
                updateItemHeader(item);
            }
        }
    });
}
