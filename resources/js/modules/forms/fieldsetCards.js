export default function initFieldsetCards(root = document) {
    root.querySelectorAll('[data-fieldset].fieldset-cards-view').forEach(container => {
        const hasHeadFields = container.querySelector('[data-head-title-field], [data-head-preview-field]');
        if (!hasHeadFields) {
            return;
        }

        updateAllItemHeaders();

        container.addEventListener('input', handleFieldChange, true);
        container.addEventListener('change', handleFieldChange, true);
        container.addEventListener('mediaChanged', handleMediaChange, true);
        container.addEventListener('dom:updated', () => {
            updateAllItemHeaders();
        });

        // Handle Edit button - show sidebar
        container.addEventListener('click', (e) => {
            const editBtn = e.target.closest('[data-action="edit"]');
            if (editBtn) {
                const item = editBtn.closest('[data-item-index]');
                if (item) {
                    const isEditing = item.classList.toggle('is-editing');
                    if (isEditing) {
                        document.body.classList.add('fieldset-editing');
                    } else {
                        document.body.classList.remove('fieldset-editing');
                    }
                }
            }
        });

        // Close sidebar on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const editing = container.querySelector('.fieldset-item.is-editing');
                if (editing) {
                    editing.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
                }
            }
        });

        // Close sidebar on overlay click
        document.addEventListener('click', (e) => {
            if (e.target === document.body || e.target.tagName === 'HTML') {
                const editing = container.querySelector('.fieldset-item.is-editing');
                if (editing) {
                    editing.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
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

            const img = mediaContainer.querySelector('img');
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
