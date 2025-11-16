import Sortable from 'sortablejs'
import { showToast } from '../ui/toast.js'
import { trans } from '../../utils/translations.js'

/**
 * Make an API request with CSRF token
 */
async function apiRequest(endpoint, payload) {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || 'Request failed')
    }

    return response.json()
}

/**
 * Initialize sortable grouped tables
 * Allows drag-n-drop reordering within groups and moving items between groups
 */
export default function initSortableGroupedTable() {
    const groupTbodies = document.querySelectorAll('tbody.group-items-tbody[data-group-id]')

    if (groupTbodies.length === 0) {
        return
    }

    groupTbodies.forEach(tbody => {
        const config = {
            slug: tbody.dataset.slug,
            groupId: tbody.dataset.groupId,
            groupColumn: tbody.dataset.groupColumn,
            orderColumn: tbody.dataset.orderColumn,
            updateEndpoint: tbody.dataset.updateEndpoint,
            groupUpdateEndpoint: tbody.dataset.groupUpdateEndpoint
        }

        Sortable.create(tbody, {
            animation: 150,
            handle: '.sortable-drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            group: {
                name: 'shared-groups',
                pull: true,
                put: true
            },
            onEnd: (evt) => handleDragEnd(evt, config)
        })
    })
}

/**
 * Handle drag end event
 */
function handleDragEnd(evt, config) {
    const itemId = evt.item.dataset.id
    const newTbody = evt.to
    const oldTbody = evt.from
    const newGroupId = newTbody.dataset.groupId
    const oldGroupId = oldTbody.dataset.groupId
    const movedToNewGroup = newGroupId !== oldGroupId

    if (movedToNewGroup) {
        handleCrossGroupDrag(itemId, newTbody, oldTbody, newGroupId, oldGroupId, config, evt)
    } else if (evt.oldIndex !== evt.newIndex) {
        handleSameGroupDrag(newTbody, newGroupId, config)
    }
}

/**
 * Handle dragging item to a different group
 */
function handleCrossGroupDrag(itemId, newTbody, oldTbody, newGroupId, oldGroupId, config, evt) {
    updateItemGroup(itemId, newGroupId, config.groupColumn, config.groupUpdateEndpoint)
        .then(() => saveGroupOrder(newTbody, newGroupId, config.orderColumn, config.updateEndpoint))
        .then(() => {
            if (oldTbody.querySelectorAll('.sortable-row').length > 0) {
                return saveGroupOrder(oldTbody, oldGroupId, config.orderColumn, config.updateEndpoint)
            }
        })
        .then(() => {
            showToast('success', trans('sortable.item_moved'))
        })
        .catch(error => {
            console.error('Error updating groups:', error)
            showToast('danger', trans('sortable.item_move_error'))
            // Revert DOM change on error
            oldTbody.insertBefore(evt.item, oldTbody.children[evt.oldIndex])
        })
}

/**
 * Handle dragging item within the same group
 */
function handleSameGroupDrag(tbody, groupId, config) {
    saveGroupOrder(tbody, groupId, config.orderColumn, config.updateEndpoint)
        .then(() => {
            showToast('success', trans('sortable.order_updated'))
        })
        .catch(error => {
            console.error('Error saving order:', error)
            showToast('danger', trans('sortable.order_error'))
        })
}

/**
 * Update item's group assignment
 */
async function updateItemGroup(itemId, newGroupId, groupColumn, endpoint) {
    return apiRequest(endpoint, {
        item_id: itemId,
        group_column: groupColumn,
        group_id: newGroupId
    })
}

/**
 * Save order for items within a group
 */
async function saveGroupOrder(tbody, groupId, orderColumn, endpoint) {
    const rows = tbody.querySelectorAll('.sortable-row')
    const order = {}

    rows.forEach((row, index) => {
        order[row.dataset.id] = index + 1
    })

    return apiRequest(endpoint, {
        order: order,
        order_column: orderColumn,
        group_id: groupId
    })
}
