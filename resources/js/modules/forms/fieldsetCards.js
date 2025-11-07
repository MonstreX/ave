export default function initFieldsetCards(root = document) {
    root.querySelectorAll('[data-fieldset].fieldset-cards-view').forEach(container => {
        const hasHeadFields = container.querySelector('[data-head-title-field], [data-head-preview-field]');
        if (!hasHeadFields) {
            return;
        }

        const itemsContainer = container.querySelector('[data-fieldset-items]');
        const fieldName = container.dataset.fieldName;

        // Helper to get sortable instance
        const getSortable = () => {
            return window.sortableInstances && window.sortableInstances[fieldName];
        };

        updateAllItemHeaders();

        container.addEventListener('input', handleFieldChange, true);
        container.addEventListener('change', handleFieldChange, true);
        container.addEventListener('mediaChanged', handleMediaChange, true);
        container.addEventListener('dom:updated', () => {
            updateAllItemHeaders();
        });

        // Handle card click - show sidebar and disable sortable
        container.addEventListener('click', (e) => {
            // Ignore clicks on buttons (delete button)
            if (e.target.closest('button')) {
                return;
            }

            // Get the fieldset item if clicking on the card header
            const cardHeader = e.target.closest('.fieldset-card-header');
            if (cardHeader) {
                const item = cardHeader.closest('[data-item-index]');
                if (item) {
                    item.classList.add('is-editing');
                    document.body.classList.add('fieldset-editing');
                    // Disable sortable when editing
                    const sortable = getSortable();
                    if (sortable) {
                        sortable.option('disabled', true);
                    }
                    itemsContainer.classList.add('sortable-disabled');
                }
            }

            // Handle close sidebar button
            const closeBtn = e.target.closest('[data-action="close-sidebar"]');
            if (closeBtn) {
                const item = closeBtn.closest('[data-item-index]');
                if (item) {
                    item.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
                    // Update preview before closing (in case media was just added)
                    updateItemHeader(item);
                    // Re-enable sortable
                    const sortable = getSortable();
                    if (sortable) {
                        sortable.option('disabled', false);
                    }
                    itemsContainer.classList.remove('sortable-disabled');
                }
            }
        });

        // Close sidebar on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const editing = container.querySelector('.fieldset-item.is-editing');
                if (editing) {
                    // Update preview before closing
                    updateItemHeader(editing);
                    editing.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
                    const sortable = getSortable();
                    if (sortable) {
                        sortable.option('disabled', false);
                    }
                    itemsContainer.classList.remove('sortable-disabled');
                }
            }
        });

        // Close sidebar on overlay click
        document.addEventListener('click', (e) => {
            if (e.target === document.body || e.target.tagName === 'HTML') {
                const editing = container.querySelector('.fieldset-item.is-editing');
                if (editing) {
                    // Update preview before closing
                    updateItemHeader(editing);
                    editing.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
                    const sortable = getSortable();
                    if (sortable) {
                        sortable.option('disabled', false);
                    }
                    itemsContainer.classList.remove('sortable-disabled');
                }
            }
        });

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

            if (titleFieldName) {
                updateItemTitle(item, titleFieldName);
            }

            if (previewFieldName) {
                updateItemPreview(item, previewFieldName);
            }
        }

        function updateItemTitle(item, fieldName) {
            const titleElement = item.querySelector('[data-item-title]');
            if (!titleElement) return;

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

            // Look for media field in the entire item (not just visible part)
            const mediaContainer = item.querySelector(`[data-field-name*="${fieldName}"]`);
            if (!mediaContainer) return;

            // Try to find first image - check all possible selectors
            const img = mediaContainer.querySelector('.media-preview img') || 
                       mediaContainer.querySelector('img[src]') ||
                       mediaContainer.querySelector('img');
            
            if (img && img.src) {
                previewElement.style.backgroundImage = `url('${img.src}')`;
                previewElement.textContent = '';
            } else {
                previewElement.style.backgroundImage = '';
                previewElement.textContent = '';
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
