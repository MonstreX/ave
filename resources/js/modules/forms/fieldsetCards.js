import Sortable from 'sortablejs';
import { confirm } from '../ui/modals.js';
import { aveEvents } from '../../core/EventBus.js';

/**
 * Initialize FieldSet Cards display variant functionality
 *
 * Handles:
 * - Card grid layout with flex
 * - Drag-and-drop sorting (always active)
 * - Click vs drag detection (distinguish between clicks and reordering)
 * - Sidebar edit form (CSS-based, no DOM movement)
 * - Adding/deleting items
 * - Item numbering and preview updates
 */
export default function initFieldsetCards(root = document) {
    root.querySelectorAll('[data-fieldset-cards]').forEach(container => {
        const fieldName = container.dataset.fieldName || 'fieldset';
        const grid = container.querySelector('[data-cards-grid]');
        const template = document.getElementById(`fieldset-card-template-${fieldName}`);
        const addButton = container.querySelector('[data-action="add"]');
        const overlay = container.querySelector('[data-overlay]');

        if (!grid || !template) {
            console.error(`FieldSet Cards setup incomplete for: ${fieldName}`);
            return;
        }

        let sortableInstance = null;
        let isDragging = false;
        let dragStartTime = 0;

        // ===== SORTABLE JS =====
        initSortable();

        function initSortable() {
            sortableInstance = new Sortable(grid, {
                animation: 150,
                handle: '.fieldset-item', // Whole card is draggable
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: () => {
                    updateItemNumbers();
                }
            });
        }

        // ===== DRAG vs CLICK DETECTION =====
        grid.addEventListener('mousedown', (e) => {
            // Don't interfere with button clicks
            if (e.target.closest('[data-action]')) return;

            isDragging = false;
            dragStartTime = Date.now();
        });

        grid.addEventListener('mousemove', () => {
            if (Date.now() - dragStartTime > 200) {
                isDragging = true;
            }
        });

        // ===== CLICK HANDLERS =====
        container.addEventListener('click', (e) => {
            // EDIT button
            const editBtn = e.target.closest('[data-action="edit"]');
            if (editBtn) {
                if (isDragging) {
                    isDragging = false;
                    return;
                }

                const item = editBtn.closest('.fieldset-item');
                closeAllSidebars();
                item.classList.add('is-editing');
                return;
            }

            // CLOSE button
            if (e.target.closest('[data-action="close"]')) {
                closeAllSidebars();
                return;
            }

            // OVERLAY click
            if (e.target.closest('[data-overlay]')) {
                closeAllSidebars();
                return;
            }

            // DELETE button
            const deleteBtn = e.target.closest('[data-action="delete"]');
            if (deleteBtn) {
                handleDelete(deleteBtn);
                return;
            }

            // ADD button
            const addBtn = e.target.closest('[data-action="add"]');
            if (addBtn) {
                handleAdd();
                return;
            }
        });

        // ===== KEYBOARD =====
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeAllSidebars();
            }
        });

        // ===== HELPER FUNCTIONS =====

        function closeAllSidebars() {
            container.querySelectorAll('.fieldset-item.is-editing')
                .forEach(item => item.classList.remove('is-editing'));
        }

        function updateItemNumbers() {
            grid.querySelectorAll('.fieldset-item').forEach((item, index) => {
                // Update order badge
                const badge = item.querySelector('.fieldset-order');
                if (badge) badge.textContent = index + 1;

                // Update form header title
                const form = item.querySelector('[data-card-form]');
                if (form) {
                    const header = form.querySelector('h3');
                    if (header) header.textContent = `Edit Item ${index + 1}`;
                }

                // Update data attribute
                item.dataset.cardIndex = index;
            });
        }

        function generateUniqueId() {
            const used = new Set();
            grid.querySelectorAll('[data-item-id]').forEach(item => {
                const id = item.dataset.itemId;
                if (id && id !== '') {
                    used.add(parseInt(id, 10));
                }
            });

            let id = 0;
            while (used.has(id)) id++;
            return id;
        }

        async function handleDelete(deleteBtn) {
            const confirmed = await confirm('Delete this item?', {
                title: 'Delete Item',
                variant: 'error',
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });

            if (!confirmed) return;

            const item = deleteBtn.closest('.fieldset-item');
            if (item) {
                item.remove();
                updateItemNumbers();
            }
        }

        function handleAdd() {
            const itemId = generateUniqueId();
            const displayIndex = grid.children.length;

            // Clone template
            const newItem = template.content.cloneNode(true);

            // Replace placeholders
            replacePlaceholders(newItem, displayIndex, itemId);

            // Add to grid
            grid.appendChild(newItem);

            // Update numbers
            updateItemNumbers();

            // Emit event for form component reinitialization
            aveEvents.emit('dom:updated', grid);
        }

        function replacePlaceholders(element, displayIndex, itemId) {
            const nodes = [element, ...element.querySelectorAll('*')];

            nodes.forEach(node => {
                // Replace in attributes
                Array.from(node.attributes || []).forEach(attr => {
                    let updated = attr.value
                        .replace(/__INDEX__/g, itemId)
                        .replace(/__ITEM__/g, itemId)
                        .replace(/__NUMBER__/g, displayIndex + 1);

                    if (updated !== attr.value) {
                        node.setAttribute(attr.name, updated);
                    }
                });

                // Replace in dataset
                Object.entries(node.dataset || {}).forEach(([key, value]) => {
                    let updated = value
                        .replace(/__INDEX__/g, itemId)
                        .replace(/__ITEM__/g, itemId)
                        .replace(/__NUMBER__/g, displayIndex + 1);

                    if (updated !== value) {
                        node.dataset[key] = updated;
                    }
                });

                // Replace in text nodes
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = node.textContent
                        .replace(/__NUMBER__/g, displayIndex + 1);
                }
            });

            // Set item attributes
            const itemElement = element.querySelector && element.querySelector('.fieldset-item');
            if (itemElement) {
                itemElement.dataset.cardIndex = displayIndex;
                itemElement.dataset.itemId = itemId;
            }
        }

        // ===== FIELD UPDATE TRACKING =====
        // Listen to form changes to update preview/title
        initializeFormTracking();

        function initializeFormTracking() {
            grid.querySelectorAll('[data-card-form]').forEach(form => {
                form.addEventListener('input', (e) => {
                    const item = form.closest('.fieldset-item');
                    if (item) {
                        updateItemPreview(item, form);
                    }
                });
            });
        }

        function updateItemPreview(item, form) {
            // This would update preview and title if form changes
            // For now, it's a placeholder for future enhancements
            // In real use, you might:
            // 1. Extract values from form fields
            // 2. Update the preview image
            // 3. Update the title text
        }
    });
}
