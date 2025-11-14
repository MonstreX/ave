import Sortable from 'sortablejs'
import { showToast } from '../ui/toast.js'

/**
 * Initialize sortable grouped tables
 * Allows drag-n-drop reordering within groups and moving items between groups
 */
export default function initSortableGroupedTable() {
    // Use specific selector to avoid conflict with sortableTable.js
    const groupTbodies = document.querySelectorAll('tbody.group-items-tbody[data-group-id]')

    if (groupTbodies.length === 0) {
        return
    }

    console.log('[Ave] Initializing sortable grouped tables:', groupTbodies.length, 'groups')

    groupTbodies.forEach(tbody => {
        const slug = tbody.dataset.slug
        const groupId = tbody.dataset.groupId
        const groupColumn = tbody.dataset.groupColumn
        const orderColumn = tbody.dataset.orderColumn
        const updateEndpoint = tbody.dataset.updateEndpoint
        const groupUpdateEndpoint = tbody.dataset.groupUpdateEndpoint

        const sortable = Sortable.create(tbody, {
            animation: 150,
            handle: '.sortable-drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            group: {
                name: 'shared-groups',
                pull: true,
                put: true
            },

            onStart: function(evt) {
                console.log('[Sortable] Drag started:', {
                    item: evt.item.dataset.id,
                    fromGroup: evt.from.dataset.groupId
                })
            },

            onEnd: function(evt) {
                const itemId = evt.item.dataset.id
                const newTbody = evt.to
                const oldTbody = evt.from
                const newGroupId = newTbody.dataset.groupId
                const oldGroupId = oldTbody.dataset.groupId
                const movedToNewGroup = newGroupId !== oldGroupId

                console.log('[Sortable] Drag ended:', {
                    item: itemId,
                    oldIndex: evt.oldIndex,
                    newIndex: evt.newIndex,
                    oldGroup: oldGroupId,
                    newGroup: newGroupId,
                    movedToNewGroup
                })

                // If moved to a different group
                if (movedToNewGroup) {
                    updateItemGroup(itemId, newGroupId, groupColumn, groupUpdateEndpoint)
                        .then(() => {
                            // After group update, save order within new group
                            return saveGroupOrder(newTbody, newGroupId, orderColumn, updateEndpoint)
                        })
                        .then(() => {
                            // Also update order in old group if it has items
                            if (oldTbody.querySelectorAll('.sortable-row').length > 0) {
                                return saveGroupOrder(oldTbody, oldGroupId, orderColumn, updateEndpoint)
                            }
                        })
                        .then(() => {
                            showToast('success', 'Item moved to new group successfully')
                        })
                        .catch(error => {
                            console.error('[Sortable] Error updating groups:', error)
                            showToast('danger', 'Error updating item group')
                            // Revert the DOM change on error
                            oldTbody.insertBefore(evt.item, oldTbody.children[evt.oldIndex])
                        })
                } else if (evt.oldIndex !== evt.newIndex) {
                    // Moved within same group - just update order
                    saveGroupOrder(newTbody, newGroupId, orderColumn, updateEndpoint)
                        .then(() => {
                            showToast('success', 'Order updated successfully')
                        })
                        .catch(error => {
                            console.error('[Sortable] Error saving order:', error)
                            showToast('danger', 'Error saving order')
                        })
                }
            }
        })
    })
}

/**
 * Update item's group assignment
 */
async function updateItemGroup(itemId, newGroupId, groupColumn, endpoint) {
    console.log('[Sortable] Updating item group:', { itemId, newGroupId, groupColumn, endpoint })

    const payload = {
        item_id: itemId,
        group_column: groupColumn,
        group_id: newGroupId
    }
    console.log('[Sortable] Request payload:', payload)

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })

    console.log('[Sortable] Response status:', response.status)

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        console.error('[Sortable] Error response:', errorData)
        throw new Error(errorData.message || 'Failed to update group')
    }

    const data = await response.json()
    console.log('[Sortable] Group updated successfully:', data)

    return data
}

/**
 * Save order for items within a group
 */
async function saveGroupOrder(tbody, groupId, orderColumn, endpoint) {
    const rows = tbody.querySelectorAll('.sortable-row')
    const order = {}

    rows.forEach((row, index) => {
        const id = row.dataset.id
        order[id] = index + 1
    })

    console.log('[Sortable] Saving order for group', groupId, ':', order)

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            order: order,
            order_column: orderColumn,
            group_id: groupId
        })
    })

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || 'Failed to save order')
    }

    const data = await response.json()
    console.log('[Sortable] Order saved successfully:', data)

    return data
}
