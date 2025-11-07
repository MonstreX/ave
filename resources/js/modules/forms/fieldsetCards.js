export default function initFieldsetCards(root = document) {
    root.querySelectorAll('[data-fieldset].fieldset-cards-view').forEach(container => {
        const hasHeadFields = container.querySelector('[data-head-title-field], [data-head-preview-field]');
        if (!hasHeadFields) {
            return;
        }

        const itemsContainer = container.querySelector('[data-fieldset-items]');
        const fieldName = container.dataset.fieldName;

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

        // Handle clicks - check close button FIRST
        container.addEventListener('click', (e) => {
            // CLOSE BUTTON - handle first before any other button logic
            const closeBtn = e.target.closest('[data-action="close-sidebar"]');
            if (closeBtn) {
                const item = closeBtn.closest('[data-item-index]');
                if (item) {
                    e.stopPropagation();
                    item.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
                    updateItemHeader(item);
                    const sortable = getSortable();
                    if (sortable) {
                        sortable.option('disabled', false);
                    }
                    itemsContainer.classList.remove('sortable-disabled');
                }
                return;
            }

            // DELETE BUTTON - ignore it
            const deleteBtn = e.target.closest('[data-action="delete"]');
            if (deleteBtn) {
                return;
            }

            // CARD HEADER CLICK - open sidebar
            const cardHeader = e.target.closest('.fieldset-card-header');
            if (cardHeader) {
                const item = cardHeader.closest('[data-item-index]');
                if (item) {
                    item.classList.add('is-editing');
                    document.body.classList.add('fieldset-editing');
                    const sortable = getSortable();
                    if (sortable) {
                        sortable.option('disabled', true);
                    }
                    itemsContainer.classList.add('sortable-disabled');
                }
                return;
            }
        });

        // Close sidebar on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const editing = container.querySelector('.fieldset-item.is-editing');
                if (editing) {
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

            const mediaContainer = item.querySelector(`[data-field-name*="${fieldName}"]`);
            if (!mediaContainer) return;

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
