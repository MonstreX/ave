import Sortable from 'sortablejs';
import { confirm, alert as showAlert } from '../ui/modals.js';
import { reinitFormComponents, reinitEditors } from './formReinit.js';
import { aveEvents } from '../../core/EventBus.js';

/**
 * Initialize FieldSet functionality
 *
 * Handles:
 * - Adding new items
 * - Deleting items
 * - Drag-and-drop sorting
 * - Item numbering
 */
export default function initFieldSet(root = document) {
    root.querySelectorAll('[data-fieldset]').forEach(container => {
        const fieldName = container.dataset.fieldName;
        const itemsContainer = container.querySelector('[data-fieldset-items]');
        const template = document.getElementById(`fieldset-template-${fieldName}`);
        const addButton = container.querySelector('[data-action="add"]');
        const sortable = container.dataset.sortable === 'true';
        const collapsible = container.dataset.collapsible === 'true';
        const minItems = parseInt(container.dataset.minItems) || 0;
        const maxItems = parseInt(container.dataset.maxItems) || null;

        if (!template) {
            console.error(`FieldSet template not found: fieldset-template-${fieldName}`);
            return;
        }

        const computeMetaKey = (value = '') => {
            if (!value) {
                return '';
            }

            return value
                .replace(/\]/g, '')
                .replace(/\[/g, '.')
                .replace(/\.+/g, '.')
                .replace(/^\./, '')
                .replace(/\.$/, '')
                .toLowerCase();
        };

        let sortableInstance = null;

        const collectUsedItemIds = () =>
            Array.from(itemsContainer.querySelectorAll('[data-field-id]'))
                .map(input => parseInt(input.value, 10))
                .filter(Number.isInteger);

        const nextItemId = () => {
            const used = new Set(collectUsedItemIds());
            let candidate = 0;

            while (used.has(candidate)) {
                candidate++;
            }

            return candidate;
        };

        const replacePlaceholders = (value, itemId) => {
            return value
                .replace(/__INDEX__/g, itemId)
                .replace(/__index__/g, itemId)
                .replace(/__ITEM__/g, itemId)
                .replace(/__item__/g, itemId);
        };

        const applyPlaceholders = (element, displayIndex, itemId) => {
            const nodes = [element, ...element.querySelectorAll('*')];

            nodes.forEach(node => {
                Array.from(node.attributes || []).forEach(attr => {
                    const updated = replacePlaceholders(attr.value, itemId);

                    if (updated !== attr.value) {
                        node.setAttribute(attr.name, updated);
                    }
                });

                Object.entries(node.dataset || {}).forEach(([key, value]) => {
                    const updated = replacePlaceholders(value, itemId);
                    if (updated !== value) {
                        node.dataset[key] = updated;
                    }
                });
            });

            element.dataset.itemIndex = displayIndex;
            element.dataset.itemId = itemId;

            // Update data-meta-key for media field containers
            // Convert state path format (e.g., "features.__ITEM__.icon") to metaKey format (e.g., "features_0_icon")
            element.querySelectorAll('[data-meta-key*=".__ITEM__."]').forEach(mediaContainer => {
                const oldMetaKey = mediaContainer.dataset.metaKey;
                // Replace __ITEM__ placeholder with actual item ID, then normalize to metaKey format
                const updatedPath = replacePlaceholders(oldMetaKey, itemId);
                // Convert state path with dots to metaKey with underscores
                const normalizedMetaKey = computeMetaKey(updatedPath);
                mediaContainer.dataset.metaKey = normalizedMetaKey;

                // Update name attributes of hidden inputs for uploaded/deleted/props data
                // This ensures request data is sent with the correct key that server expects
                mediaContainer.querySelectorAll('input[data-uploaded-ids], input[data-deleted-ids], input[data-media-props="true"]').forEach(input => {
                    if (input.name && input.name.includes(oldMetaKey)) {
                        input.name = input.name.replace(oldMetaKey, normalizedMetaKey);
                    }
                });
            });
        };

        // Initialize Sortable.js for drag-and-drop (disabled by default)
        if (sortable) {
            sortableInstance = Sortable.create(itemsContainer, {
                animation: 400,
                easing: 'cubic-bezier(0.25, 0.8, 0.25, 1)',
                handle: '.fieldset-drag-handle',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                disabled: true, // Disabled by default, enabled via Sort Mode toggle
                onEnd: () => {
                    updateItemNumbers();
                }
            });
        }

        // Add new item
        if (addButton) {
            addButton.addEventListener('click', () => {
                // Check max items limit
                const currentCount = itemsContainer.querySelectorAll('.fieldset-item').length;
                if (maxItems && currentCount >= maxItems) {
                    alert(`Maximum ${maxItems} items allowed`);
                    return;
                }

                // Clone template
                const newItem = template.content.cloneNode(true);

                // Append to DOM first
                itemsContainer.appendChild(newItem);

                // Find the added item
                const addedItem = itemsContainer.lastElementChild;

                if (!addedItem) {
                    console.error('Failed to add fieldset item');
                    return;
                }

                const itemId = nextItemId();

                applyPlaceholders(addedItem, currentCount, itemId);

                const idInput = addedItem.querySelector('[data-field-id]');
                if (idInput) {
                    idInput.value = itemId;
                }

                addedItem.querySelectorAll('.media-field-container').forEach(mediaContainer => {
                    mediaContainer.dataset.initialized = 'false';
                });

                // Initialize components AFTER updating collection names
                reinitFormComponents(addedItem);
                void reinitEditors(addedItem);

                // Emit event for listeners to reinitialize their own components
                // This supports both direct subscribers and form reinitialization setup
                aveEvents.emit('dom:updated', addedItem);
                updateItemNumbers();
                updateItemHeader(addedItem);
            });
        }

        // Delete item (event delegation)
        itemsContainer.addEventListener('click', async (e) => {
            const deleteButton = e.target.closest('[data-action="delete"]');
            if (!deleteButton) return;

            const item = deleteButton.closest('.fieldset-item');
            if (!item) return;

            // Check min items limit
            const currentCount = itemsContainer.querySelectorAll('.fieldset-item').length;
            if (minItems > 0 && currentCount <= minItems) {
                showAlert(`Minimum ${minItems} items required`, {
                    title: 'Deletion Blocked',
                    variant: 'warning',
                    confirmText: 'OK'
                });
                return;
            }

            // Confirm deletion
            const confirmed = await confirm('Are you sure you want to delete this item?', {
                title: 'Delete Item',
                variant: 'error',
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });

            if (!confirmed) return;

            // Delete media collections before removing item
            await deleteFieldSetMediaCollections(item);

            // Remove item
            item.remove();

            // Update numbers
            updateItemNumbers();
        });

        // Collapse/Expand item (event delegation)
        if (collapsible) {
            itemsContainer.addEventListener('click', (e) => {
                const collapseButton = e.target.closest('[data-action="collapse"]');
                if (!collapseButton) return;

                const item = collapseButton.closest('.fieldset-item');
                if (!item) return;

                const fieldsContainer = item.querySelector('.fieldset-item-fields');
                if (!fieldsContainer) return;

                // Toggle collapsed state
                const isCollapsed = item.classList.contains('collapsed');

                if (isCollapsed) {
                    // Expand
                    item.classList.remove('collapsed');
                    fieldsContainer.style.maxHeight = fieldsContainer.scrollHeight + 'px';

                    // Remove max-height after animation completes
                    setTimeout(() => {
                        fieldsContainer.style.maxHeight = '';
                    }, 300);
                } else {
                    // Collapse
                    fieldsContainer.style.maxHeight = fieldsContainer.scrollHeight + 'px';
                    // Force reflow
                    fieldsContainer.offsetHeight;
                    fieldsContainer.style.maxHeight = '0';
                    item.classList.add('collapsed');
                }
            });
        }

        // Collapse All
        const collapseAllButton = container.querySelector('[data-action="collapse-all"]');
        if (collapseAllButton) {
            collapseAllButton.addEventListener('click', () => {
                const items = itemsContainer.querySelectorAll('.fieldset-item');
                items.forEach(item => {
                    if (!item.classList.contains('collapsed')) {
                        const fieldsContainer = item.querySelector('.fieldset-item-fields');
                        if (fieldsContainer) {
                            fieldsContainer.style.maxHeight = fieldsContainer.scrollHeight + 'px';
                            fieldsContainer.offsetHeight;
                            fieldsContainer.style.maxHeight = '0';
                            item.classList.add('collapsed');
                        }
                    }
                });
            });
        }

        // Expand All
        const expandAllButton = container.querySelector('[data-action="expand-all"]');
        if (expandAllButton) {
            expandAllButton.addEventListener('click', () => {
                const items = itemsContainer.querySelectorAll('.fieldset-item');
                items.forEach(item => {
                    if (item.classList.contains('collapsed')) {
                        const fieldsContainer = item.querySelector('.fieldset-item-fields');
                        if (fieldsContainer) {
                            item.classList.remove('collapsed');
                            fieldsContainer.style.maxHeight = fieldsContainer.scrollHeight + 'px';
                            setTimeout(() => {
                                fieldsContainer.style.maxHeight = '';
                            }, 300);
                        }
                    }
                });
            });
        }

        // Toggle Sort Mode
        const sortToggle = container.querySelector('[data-action="toggle-sort"]');
        if (sortToggle && sortableInstance) {
            const setActionButtonsState = (enabled) => {
                [collapseAllButton, expandAllButton].forEach(button => {
                    if (!button) return;
                    button.disabled = !enabled;
                    button.classList.toggle('is-disabled', !enabled);
                });
            };

            setActionButtonsState(!sortToggle.checked);

            sortToggle.addEventListener('change', (e) => {
                const sortModeEnabled = e.target.checked;

                if (sortModeEnabled) {
                    // Enable sort mode
                    container.classList.add('sort-mode');
                    sortableInstance.option('disabled', false);
                    setActionButtonsState(false);

                    // Collapse all items
                    const items = itemsContainer.querySelectorAll('.fieldset-item');
                    items.forEach(item => {
                        const fieldsContainer = item.querySelector('.fieldset-item-fields');
                        if (fieldsContainer) {
                            fieldsContainer.style.maxHeight = '0';
                            item.classList.add('collapsed');
                        }
                    });
                } else {
                    // Disable sort mode
                    container.classList.remove('sort-mode');
                    sortableInstance.option('disabled', true);
                    setActionButtonsState(true);

                    // Expand all items
                    const items = itemsContainer.querySelectorAll('.fieldset-item');
                    items.forEach(item => {
                        const fieldsContainer = item.querySelector('.fieldset-item-fields');
                        if (fieldsContainer) {
                            item.classList.remove('collapsed');
                            fieldsContainer.style.maxHeight = '';
                        }
                    });
                }
            });
        }

        /**
         * Update item numbers after adding/deleting/sorting
         */
        function updateItemNumbers() {
            const items = itemsContainer.querySelectorAll('.fieldset-item');
            items.forEach((item, index) => {
                item.dataset.itemIndex = index;
                const numberBadge = item.querySelector('.fieldset-item-number');
                if (numberBadge) {
                    numberBadge.textContent = index + 1;
                }
            });
        }

        // Initial numbering
        updateItemNumbers();

        // Initialize header titles and previews for existing items
        initializeItemHeaders();

        // Watch for field changes to update headers
        itemsContainer.addEventListener('input', (e) => {
            const item = e.target.closest('.fieldset-item');
            if (!item) return;

            updateItemHeader(item);
        });

        // Watch for media changes (added/deleted)
        itemsContainer.addEventListener('mediaChanged', (e) => {
            const item = e.target.closest('.fieldset-item');
            if (!item) return;

            updateItemHeader(item);
        });

        /**
         * Initialize headers for all existing items
         */
        function initializeItemHeaders() {
            const items = itemsContainer.querySelectorAll('.fieldset-item');
            items.forEach(item => updateItemHeader(item));
        }

        /**
         * Update item header (title and preview)
         */
        function updateItemHeader(item) {
            const header = item.querySelector('.fieldset-item-header');
            if (!header) return;

            const titleFieldName = header.dataset.headTitleField;
            const previewFieldName = header.dataset.headPreviewField;

            // Update title
            if (titleFieldName) {
                const titleElement = header.querySelector('[data-item-title]');
                const titleField = item.querySelector(`[name$="[${titleFieldName}]"]`);

                if (titleElement && titleField) {
                    titleElement.textContent = titleField.value || '';
                }
            }

            // Update preview
            if (previewFieldName) {
                const previewElement = header.querySelector('[data-item-preview]');
                const mediaField = item.querySelector(`[data-field-name*="${previewFieldName}"]`);

                if (previewElement && mediaField) {
                    // Find first media item image
                    const firstImage = mediaField.querySelector('.media-preview img');

                    if (firstImage) {
                        previewElement.style.backgroundImage = `url(${firstImage.src})`;
                        previewElement.style.display = 'block';
                    } else {
                        previewElement.style.backgroundImage = '';
                        previewElement.style.display = 'none';
                    }
                }
            }
        }
    });
}

/**
 * Delete all media collections associated with a FieldSet item
 * @param {HTMLElement} item - FieldSet item element
 */
async function deleteFieldSetMediaCollections(item) {
    // Find all MediaField containers in this FieldSet item
    const mediaContainers = item.querySelectorAll('.media-field-container');

    for (const mediaContainer of mediaContainers) {
        const collectionName = mediaContainer.dataset.collection;

        // Check if this is a FieldSet collection (contains underscores like features_1_preview)
        if (!collectionName || !collectionName.includes('_')) continue;

        const modelType = mediaContainer.dataset.modelType;
        const modelId = mediaContainer.dataset.modelId;
        const uploadUrl = mediaContainer.dataset.uploadUrl;

        if (!modelType || !modelId || !uploadUrl) continue;

        // Call endpoint to delete the collection
        await deleteMediaCollection(collectionName, modelType, modelId, uploadUrl);
    }
}

/**
 * Delete a media collection via API
 * @param {string} collectionName - Collection name to delete
 * @param {string} modelType - Model class name
 * @param {string|number} modelId - Model ID
 * @param {string} uploadUrl - Upload URL to derive base path
 */
async function deleteMediaCollection(collectionName, modelType, modelId, uploadUrl) {
    // Build delete URL from upload URL (e.g., /admin/media/upload -> /admin/media/collection)
    const deleteUrl = uploadUrl.replace('/upload', '/collection');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        const response = await fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                collection: collectionName,
                model_type: modelType,
                model_id: modelId
            })
        });

        if (!response.ok) {
            const text = await response.text();
            throw new Error(text || `HTTP ${response.status}`);
        }

        return response.json();
    } catch (error) {
        console.error('Failed to delete media collection:', error);
    }
}

