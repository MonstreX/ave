/**
 * Sortable Table with drag-n-drop row reordering using SortableJS
 */
import Sortable from 'sortablejs';
import { showToast } from '../ui/toast.js';
import { trans } from '../../utils/translations.js';

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

    // Initialize SortableJS on tbody with handle
    const sortable = Sortable.create(tbody, {
        animation: 150,
        handle: '.sortable-drag-handle',  // Nested inside td, but should work
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        
        onStart: function(evt) {
            console.log('Drag started:', evt.item);
        },
        
        onEnd: function(evt) {
            console.log('Drag ended. Old index:', evt.oldIndex, 'New index:', evt.newIndex);
            if (evt.oldIndex !== evt.newIndex) {
                saveSortableOrder(tbody, slug, orderColumn, updateEndpoint);
            }
        }
    });

    tbody.sortableInstance = sortable;
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || trans('sortable.order_updated'));
        } else {
            showToast('danger', data.message || trans('sortable.order_update_failed'));
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Sortable table update error:', error);
        showToast('danger', trans('sortable.table_order_failed'));
        setTimeout(() => window.location.reload(), 1500);
    });
}
