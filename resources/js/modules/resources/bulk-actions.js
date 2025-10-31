/**
 * Bulk Actions Manager
 *
 * Handles bulk action selection, toolbar visibility, and handler execution
 */

import { createModal, destroyModal } from '../ui/modals.js';
import { showToast } from '../ui/toast.js';

export function setupBulkActions() {
    const bulkToolbar = document.getElementById('bulk-actions-toolbar');
    if (!bulkToolbar) {
        return;
    }

    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-selector');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionButtons = bulkToolbar.querySelectorAll('.bulk-action-btn');

    /**
     * Update toolbar visibility and selected count
     */
    function updateToolbar() {
        const selectedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
        selectedCountSpan.textContent = selectedCount;

        if (selectedCount > 0) {
            bulkToolbar.classList.add('is-visible');
            bulkActionButtons.forEach(btn => {
                btn.disabled = false;
            });
        } else {
            bulkToolbar.classList.remove('is-visible');
            selectAllCheckbox.checked = false;
        }
    }

    /**
     * Handle select all checkbox
     */
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
            updateToolbar();
        });
    }

    /**
     * Handle individual row checkboxes
     */
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const allChecked = Array.from(rowCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(rowCheckboxes).some(cb => cb.checked);

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }

            updateToolbar();
        });
    });

    /**
     * Handle bulk action button clicks
     */
    bulkActionButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();

            const selectedIds = Array.from(rowCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            if (selectedIds.length === 0) {
                showToast('warning', 'Please select at least one record');
                return;
            }

            const handlerClass = button.dataset.handler;
            const handlerLabel = button.textContent.trim();
            const handlerVariant = button.dataset.variant || 'primary';

            button.disabled = true;
            button.textContent = handlerLabel + '...';

            // Show confirmation dialog with variant styling
            const modal = createModal({
                title: `${handlerLabel} ${selectedIds.length} Record(s)?`,
                body: `Are you sure you want to ${handlerLabel.toLowerCase()} ${selectedIds.length} selected record(s)?`,
                type: 'confirm',
                variant: handlerVariant,
                confirmText: handlerLabel,
                cancelText: 'Cancel',
                size: 'small',
                autoClose: false,
                onConfirm: async () => {
                    try {
                        // Send DELETE requests for each record
                        await Promise.all(
                            selectedIds.map(id =>
                                fetch(`${window.location.pathname.replace(/\/$/, '')}/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                        'Accept': 'application/json'
                                    }
                                })
                            )
                        );

                        // Close modal
                        destroyModal(modal);

                        // Reload page to reflect changes
                        setTimeout(() => {
                            showToast('success', `Successfully ${handlerLabel.toLowerCase()} ${selectedIds.length} record(s)`);
                            window.location.reload();
                        }, 300);
                    } catch (error) {
                        destroyModal(modal);
                        showToast('danger', 'Error executing action: ' + error.message);
                        button.disabled = false;
                        button.textContent = handlerLabel;
                    }
                },
                onCancel: () => {
                    button.disabled = false;
                    button.textContent = handlerLabel;
                }
            });
        });
    });

    // Initialize toolbar visibility
    updateToolbar();
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupBulkActions);
} else {
    setupBulkActions();
}
