/**
 * Tree View with nested drag-n-drop using SortableJS
 */
import Sortable from 'sortablejs';
import { showToast } from '../ui/toast.js';
import { ANIMATION_DURATIONS, ANIMATION_EASING } from '../forms/formConstants.js';

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

export default function initTreeView(root = document) {
    root.querySelectorAll('[data-tree="true"]').forEach(container => {
        initializeTree(container);
    });
}

function initializeTree(container) {
    const slug = container.dataset.slug;
    const parentColumn = container.dataset.parentColumn;
    const orderColumn = container.dataset.orderColumn;
    const maxDepth = parseInt(container.dataset.maxDepth || '5');
    const updateEndpoint = container.dataset.updateEndpoint;

    // Initialize expand/collapse buttons
    initExpandCollapse(container);

    // Initialize SortableJS for all lists (including nested)
    initSortableNested(container, maxDepth, slug, parentColumn, orderColumn, updateEndpoint);
}

function initExpandCollapse(container) {
    // Global expand/collapse all
    const expandAllBtn = document.querySelector('.tree-expand-all');
    const collapseAllBtn = document.querySelector('.tree-collapse-all');

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', () => {
            container.querySelectorAll('.tree-list').forEach(list => {
                list.style.display = '';
            });
            container.querySelectorAll('[data-action="collapse"]').forEach(btn => {
                btn.classList.remove('hidden');
            });
            container.querySelectorAll('[data-action="expand"]').forEach(btn => {
                btn.classList.add('hidden');
            });
        });
    }

    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', () => {
            container.querySelectorAll('.tree-item > .tree-list').forEach(list => {
                list.style.display = 'none';
            });
            container.querySelectorAll('[data-action="collapse"]').forEach(btn => {
                btn.classList.add('hidden');
            });
            container.querySelectorAll('[data-action="expand"]').forEach(btn => {
                btn.classList.remove('hidden');
            });
        });
    }

    // Individual item expand/collapse
    container.querySelectorAll('.dd-item-btns button').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const action = btn.dataset.action;
            const treeItem = btn.closest('.tree-item');
            const childList = treeItem.querySelector(':scope > .tree-list');

            if (!childList) return;

            if (action === 'collapse') {
                childList.style.display = 'none';
                btn.classList.add('hidden');
                treeItem.querySelector('[data-action="expand"]')?.classList.remove('hidden');
            } else {
                childList.style.display = '';
                btn.classList.add('hidden');
                treeItem.querySelector('[data-action="collapse"]')?.classList.remove('hidden');
            }
        });
    });
}

function initSortableNested(container, maxDepth, slug, parentColumn, orderColumn, updateEndpoint) {
    const lists = container.querySelectorAll('.tree-list');

    lists.forEach((list) => {
        Sortable.create(list, {
            group: 'tree-nested',
            animation: ANIMATION_DURATIONS.SORTABLE,
            easing: ANIMATION_EASING.SORTABLE,
            handle: '.tree-drag-handle',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',

            // Prevent nesting beyond maxDepth
            onMove: (evt) => {
                const draggedItem = evt.dragged;
                const toList = evt.to;

                // Calculate depth of target location
                const depth = getListDepth(toList);
                const itemDepth = getItemMaxDepth(draggedItem);

                if (depth + itemDepth > maxDepth) {
                    return false; // Prevent move
                }
            },

            onEnd: (evt) => {
                saveTreeStructure(container, slug, parentColumn, orderColumn, updateEndpoint);
            }
        });
    });
}

function getListDepth(list) {
    let depth = 0;
    let current = list;

    while (current && !current.matches('[data-tree="true"]')) {
        if (current.matches('.tree-list')) {
            depth++;
        }
        current = current.parentElement;
    }

    return depth;
}

function getItemMaxDepth(item) {
    let maxDepth = 1;
    const childList = item.querySelector(':scope > .tree-list');

    if (childList) {
        const children = childList.querySelectorAll(':scope > .tree-item');
        children.forEach(child => {
            const childDepth = getItemMaxDepth(child);
            maxDepth = Math.max(maxDepth, 1 + childDepth);
        });
    }

    return maxDepth;
}

function serializeTree(container) {
    const rootList = container.querySelector(':scope > .tree-list');
    if (!rootList) return [];

    return serializeList(rootList);
}

function serializeList(list) {
    const items = [];
    const directChildren = list.querySelectorAll(':scope > .tree-item');

    directChildren.forEach(item => {
        const itemId = parseInt(item.dataset.id);
        const childList = item.querySelector(':scope > .tree-list');

        const serialized = { id: itemId };

        if (childList) {
            const children = serializeList(childList);
            if (children.length > 0) {
                serialized.children = children;
            }
        }

        items.push(serialized);
    });

    return items;
}

function saveTreeStructure(container, slug, parentColumn, orderColumn, updateEndpoint) {
    const tree = serializeTree(container);

    fetch(updateEndpoint, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            tree: tree,
            parent_column: parentColumn,
            order_column: orderColumn
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Tree structure updated');
        } else {
            showToast('danger', data.message || 'Failed to update tree');
        }
    })
    .catch(error => {
        console.error('Tree update error:', error);
        showToast('danger', 'Failed to update tree structure');
    });
}
