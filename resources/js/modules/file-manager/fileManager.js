import { showToast } from '../ui/toast.js'
import { trans } from '../../utils/translations.js'
import { createModal, closeModal, destroyModal } from '../ui/modals.js'

let currentPath = ''
let routes = {}
let currentModal = null

export default function initFileManager() {
    const fileList = document.getElementById('fm-file-list')
    if (!fileList) return

    // Get routes from hidden input
    const routesInput = document.getElementById('fm-routes')
    if (routesInput) {
        routes = {
            list: routesInput.dataset.list,
            read: routesInput.dataset.read,
            save: routesInput.dataset.save,
            directory: routesInput.dataset.directory,
            upload: routesInput.dataset.upload,
            delete: routesInput.dataset.delete,
            rename: routesInput.dataset.rename,
        }
    }

    // Get current path
    const pathInput = document.getElementById('fm-current-path-input')
    if (pathInput) {
        currentPath = pathInput.value
    }

    initNavigationEvents()
    initActionEvents()
}

function initNavigationEvents() {
    // Navigation clicks (folders and breadcrumbs)
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('.fm-navigate')
        if (!link) return

        e.preventDefault()
        const path = link.dataset.fmPath ?? ''
        await navigateTo(path)
    })
}

function initActionEvents() {
    // Edit file
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('.fm-edit')
        if (!link) return

        e.preventDefault()
        const path = link.dataset.fmPath
        await openEditor(path)
    })

    // Delete
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('.fm-delete')
        if (!link) return

        e.preventDefault()
        const path = link.dataset.fmPath
        await confirmDelete(path)
    })

    // Rename button
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.fm-rename-btn')
        if (!link) return

        e.preventDefault()
        const path = link.dataset.fmPath
        const name = link.dataset.fmName
        openRenameModal(path, name)
    })

    // Upload button
    const uploadBtn = document.getElementById('fm-upload-btn')
    if (uploadBtn) {
        uploadBtn.addEventListener('click', openUploadModal)
    }

    // New folder button
    const newFolderBtn = document.getElementById('fm-new-folder-btn')
    if (newFolderBtn) {
        newFolderBtn.addEventListener('click', openFolderModal)
    }
}

async function navigateTo(path) {
    window.location.href = `${window.location.pathname}?path=${encodeURIComponent(path)}`
}

async function openEditor(path) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(`${routes.read}?path=${encodeURIComponent(path)}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        })

        const data = await response.json()

        if (data.error) {
            showToast('danger', data.error)
            return
        }

        // Create editor modal
        currentModal = createModal({
            title: data.name,
            body: `<div class="form-group">
                <textarea id="fm-editor-content" class="form-control" rows="20" style="font-family: monospace; width: 100%;">${escapeHtml(data.content)}</textarea>
            </div>`,
            type: 'form',
            size: 'large',
            confirmText: trans('common.save'),
            cancelText: trans('common.cancel'),
            autoClose: false,
            onConfirm: async (modal) => {
                const content = modal.querySelector('#fm-editor-content').value
                await saveFile(path, content)
            }
        })
    } catch (error) {
        console.error('Read error:', error)
        showToast('danger', trans('file_manager.error'))
    }
}

async function saveFile(path, content) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(routes.save, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path, content })
        })

        const data = await response.json()

        if (data.success) {
            showToast('success', trans('file_manager.file_saved'))
            if (currentModal) {
                destroyModal(currentModal)
                currentModal = null
            }
        } else {
            showToast('danger', data.error || trans('file_manager.error'))
        }
    } catch (error) {
        console.error('Save error:', error)
        showToast('danger', trans('file_manager.error'))
    }
}

function openFolderModal() {
    currentModal = createModal({
        title: trans('file_manager.new_folder'),
        body: `<div class="form-group">
            <label>${trans('file_manager.folder_name')}</label>
            <input type="text" class="form-control" id="fm-folder-name" placeholder="${trans('file_manager.folder_name_placeholder')}">
        </div>`,
        type: 'form',
        confirmText: trans('common.create'),
        cancelText: trans('common.cancel'),
        autoClose: false,
        onConfirm: async (modal) => {
            const name = modal.querySelector('#fm-folder-name').value.trim()
            if (name) {
                await createFolder(name)
            }
        }
    })
}

async function createFolder(name) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(routes.directory, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path: currentPath, name })
        })

        const data = await response.json()

        if (data.success) {
            showToast('success', trans('file_manager.folder_created'))
            if (currentModal) {
                destroyModal(currentModal)
                currentModal = null
            }
            window.location.reload()
        } else {
            showToast('danger', data.error || trans('file_manager.error'))
        }
    } catch (error) {
        console.error('Create folder error:', error)
        showToast('danger', trans('file_manager.error'))
    }
}

function openUploadModal() {
    currentModal = createModal({
        title: trans('file_manager.upload_file'),
        body: `<div class="form-group">
            <input type="file" class="form-control" id="fm-upload-input">
        </div>`,
        type: 'form',
        confirmText: trans('file_manager.upload'),
        cancelText: trans('common.cancel'),
        autoClose: false,
        onConfirm: async (modal) => {
            const fileInput = modal.querySelector('#fm-upload-input')
            if (fileInput && fileInput.files.length) {
                await uploadFile(fileInput.files[0])
            }
        }
    })
}

async function uploadFile(file) {
    const formData = new FormData()
    formData.append('path', currentPath)
    formData.append('file', file)

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(routes.upload, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData
        })

        const data = await response.json()

        if (data.success) {
            showToast('success', trans('file_manager.file_uploaded'))
            if (currentModal) {
                destroyModal(currentModal)
                currentModal = null
            }
            window.location.reload()
        } else {
            showToast('danger', data.error || trans('file_manager.error'))
        }
    } catch (error) {
        console.error('Upload error:', error)
        showToast('danger', trans('file_manager.error'))
    }
}

async function confirmDelete(path) {
    currentModal = createModal({
        title: trans('modals.delete_confirm'),
        body: trans('file_manager.confirm_delete'),
        type: 'confirm',
        variant: 'warning',
        confirmText: trans('common.delete'),
        cancelText: trans('common.cancel'),
        onConfirm: async () => {
            await deleteItem(path)
        }
    })
}

async function deleteItem(path) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(routes.delete, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path })
        })

        const data = await response.json()

        if (data.success) {
            showToast('success', trans('file_manager.deleted'))
            window.location.reload()
        } else {
            showToast('danger', data.error || trans('file_manager.error'))
        }
    } catch (error) {
        console.error('Delete error:', error)
        showToast('danger', trans('file_manager.error'))
    }
}

function openRenameModal(path, name) {
    currentModal = createModal({
        title: trans('file_manager.rename'),
        body: `<div class="form-group">
            <label>${trans('file_manager.new_name')}</label>
            <input type="text" class="form-control" id="fm-rename-input" value="${escapeHtml(name)}">
        </div>`,
        type: 'form',
        confirmText: trans('common.save'),
        cancelText: trans('common.cancel'),
        autoClose: false,
        onConfirm: async (modal) => {
            const newName = modal.querySelector('#fm-rename-input').value.trim()
            if (newName) {
                await renameItem(path, newName)
            }
        }
    })
}

async function renameItem(path, name) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        const response = await fetch(routes.rename, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ path, name })
        })

        const data = await response.json()

        if (data.success) {
            showToast('success', trans('file_manager.renamed'))
            if (currentModal) {
                destroyModal(currentModal)
                currentModal = null
            }
            window.location.reload()
        } else {
            showToast('danger', data.error || trans('file_manager.error'))
        }
    } catch (error) {
        console.error('Rename error:', error)
        showToast('danger', trans('file_manager.error'))
    }
}

function escapeHtml(text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
}
