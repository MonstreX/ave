import { updateItemHeader, updateAllItemHeaders } from './fieldset/headerUpdater.js';
import { getSortable } from './fieldset/sortableManager.js';

export default function initFieldsetCards(root = document) {
    root.querySelectorAll('[data-fieldset].fieldset-cards-view').forEach(container => {
        const hasHeadFields = container.querySelector('[data-head-title-field], [data-head-preview-field]');
        if (!hasHeadFields) {
            return;
        }

        const itemsContainer = container.querySelector('[data-fieldset-items]');
        const fieldName = container.dataset.fieldName;

        updateAllItemHeaders();

        container.addEventListener('input', handleFieldChange, true);
        container.addEventListener('change', handleFieldChange, true);
        container.addEventListener('mediaChanged', handleMediaChange, true);

        container.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-action="close-sidebar"]');
            if (closeBtn) {
                const item = closeBtn.closest('[data-item-index]');
                if (item) {
                    e.stopPropagation();
                    item.classList.remove('is-editing');
                    document.body.classList.remove('fieldset-editing');
                    updateItemHeader(item);
                    const sortable = getSortable(fieldName);
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
                    const sortable = getSortable(fieldName);
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
                    const sortable = getSortable(fieldName);
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
                    const sortable = getSortable(fieldName);
                    if (sortable) {
                        sortable.option('disabled', false);
                    }
                    itemsContainer.classList.remove('sortable-disabled');
                }
            }
        });

        // Note: updateItemHeader, updateItemTitle, updateItemPreview are now imported from headerUpdater.js
        // This centralizes the logic for both default and cards display modes

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
