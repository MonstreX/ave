import { createModal } from './modals.js'
import { showToast } from './toast.js'

export default function initStorageLink() {
    const storageLinkData = document.getElementById('ave-storage-link-data')
    if (!storageLinkData) {
        return
    }

    try {
        const data = JSON.parse(storageLinkData.textContent)
        if (data.missing) {
            showStorageLinkModal(data)
        }
    } catch (e) {
        console.error('Failed to parse storage link data:', e)
    }
}

function showStorageLinkModal(data) {
    createModal({
        title: data.title,
        body: data.message,
        type: 'alert',
        variant: 'warning',
        confirmText: data.createButton,
        size: 'small',
        autoClose: false,
        onConfirm: async (modal) => {
            const confirmBtn = modal.querySelector('[data-modal-confirm]')
            if (confirmBtn) {
                confirmBtn.disabled = true
                confirmBtn.textContent = '...'
            }

            try {
                const response = await fetch(data.createUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })

                const result = await response.json()

                if (result.success) {
                    showToast('success', result.message)
                    // Close and remove modal
                    modal.classList.remove('is-active')
                    setTimeout(() => modal.remove(), 300)
                } else {
                    showToast('error', result.message)
                    if (confirmBtn) {
                        confirmBtn.disabled = false
                        confirmBtn.textContent = data.createButton
                    }
                }
            } catch (e) {
                console.error('Failed to create storage link:', e)
                showToast('error', 'Failed to create storage link')
                if (confirmBtn) {
                    confirmBtn.disabled = false
                    confirmBtn.textContent = data.createButton
                }
            }
        }
    })
}
