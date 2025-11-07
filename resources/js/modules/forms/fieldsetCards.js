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

        container.addEventListener('click', (e) => {
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

            const deleteBtn = e.target.closest('[data-action="delete"]');
            if (deleteBtn) {
                return;
            }

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

            // Search ALL descendants of item for media container (including hidden sidebar)
            const allContainers = item.querySelectorAll(`[data-field-name*="${fieldName}"]`);
            if (allContainers.length === 0) return;

            // Get first matching container
            const mediaContainer = allContainers[0];

            // Find FIRST image in container - this is from first media item
            const firstImg = mediaContainer.querySelector('img');

            if (firstImg && firstImg.src && firstImg.src.length > 0) {
                previewElement.style.backgroundImage = "url('" + firstImg.src + "')";
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
