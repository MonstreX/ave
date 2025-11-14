/**
 * Sortable Table with drag-n-drop row reordering using SortableJS
 */
import Sortable from 'sortablejs';
import { showToast } from '../ui/toast.js';
import { ANIMATION_DURATIONS } from '../forms/formConstants.js';

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

export default function initSortableTable(root = document) {
    root.querySelectorAll('[data-sortable="true"]').forEach(tbody => {
        initializeSortable(tbody);
    });
}

function initializeSortable(tbody) {
    const slug = tbody.dataset.slug;
    const orderColumn = tbody.dataset.orderColumn || 'order';
    const updateEndpoint = tbody.dataset.updateEndpoint;

    // Skip if already initialized
    if (tbody.sortableInstance) {
        return;
    }

    // Initialize SortableJS on tbody
    tbody.sortableInstance = Sortable.create(tbody, {
        animation: ANIMATION_DURATIONS.SORTABLE || 150,
        handle: '.sortable-drag-handle',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        direction: 'vertical',

        onEnd: (evt) => {
            // Save new order after drag ends
            saveSortableOrder(tbody, slug, orderColumn, updateEndpoint);
        }
    });
}

/**
 * Serialize table rows to flat array
 */
function serializeSortableTable(tbody, orderColumn) {
    const rows = tbody.querySelectorAll('tr[data-id]');
    const items = [];

    rows.forEach((row, index) => {
        const id = parseInt(row.dataset.id);
        items.push({
            id: id,
            [orderColumn]: index
        });
    });

    return items;
}

/**
 * Save sortable table order to backend
 */
function saveSortableOrder(tbody, slug, orderColumn, updateEndpoint) {
    const items = serializeSortableTable(tbody, orderColumn);

    fetch(updateEndpoint, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            items: items
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Order updated successfully');
        } else {
            showToast('danger', data.message || 'Failed to update order');
            // Revert order on failure
            revertSortableOrder(tbody);
        }
    })
    .catch(error => {
        console.error('Sortable table update error:', error);
        showToast('danger', 'Failed to update table order');
        // Revert order on error
        revertSortableOrder(tbody);
    });
}

/**
 * Revert table order by reloading the page
 */
function revertSortableOrder(tbody) {
    // Simple approach: reload page to restore original order
    // Alternative: store original order before drag and restore from memory
    setTimeout(() => {
        window.location.reload();
    }, 1500);
}
