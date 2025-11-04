import Sortable from 'sortablejs';
import { confirm, createModal, destroyModal } from '../ui/modals.js';
import { showToast } from '../ui/toast.js';
import { aveEvents } from '../../core/EventBus.js';

/**
 * Convert bytes to human-readable format
 */
function humanFileSize(bytes, decimals = 1) {
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if (bytes <= 0) return '0B';
    const factor = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, factor)).toFixed(decimals) + sizes[factor];
}

// Store all media field containers for later reference
const mediaContainers = new Map();

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

export function updateAllMediaHiddenInputs() {
    mediaContainers.forEach((data) => {
        if (data.uploadedIdsInput) {
            data.uploadedIdsInput.value = data.uploadedIds.join(',');
        }
        if (data.deletedIdsInput) {
            data.deletedIdsInput.value = data.deletedIds.join(',');
        }
    });
}

export default function initMediaFields(root = document) {
    root.querySelectorAll('.media-field-container').forEach((container) => {
        // Skip if already initialized
        if (container.dataset.initialized === 'true') {
            return;
        }

        container.dataset.initialized = 'true';

        const multiple = container.dataset.multiple === 'true';
        const maxFiles = parseInt(container.dataset.maxFiles) || null;
        const maxSize = parseInt(container.dataset.maxSize) || null;
        const accept = container.dataset.accept || '';
        const uploadUrl = container.dataset.uploadUrl;
        const collection = container.dataset.collection || 'default';
        const modelType = container.dataset.modelType || '';
        const modelId = container.dataset.modelId || '';
        const fieldName = container.closest('[data-field-name]')?.dataset.fieldName || 'media';
        let metaKey = computeMetaKey(container.dataset.metaKey || fieldName);
        container.dataset.metaKey = metaKey;
        const propNames = JSON.parse(container.dataset.propNames || '[]');

        console.debug('Init media field container', {
            rawFieldName: container.closest('[data-field-name]')?.getAttribute('data-field-name') || null,
            datasetFieldName: fieldName,
            metaKey,
            collection,
        });

        const uploadArea = container.querySelector('[data-media-dropzone]');
        const fileInput = container.querySelector('.media-file-input');
        const grid = container.querySelector('[data-media-grid]');
        const uploadedIdsInput = container.querySelector('[data-uploaded-ids]');
        const deletedIdsInput = container.querySelector('[data-deleted-ids]');

        const uploadedIds = [];
        const deletedIds = [];
        const containerKey = metaKey || fieldName;

        // Store reference for later use
        mediaContainers.set(containerKey, {
            uploadedIds,
            deletedIds,
            uploadedIdsInput,
            deletedIdsInput,
            fieldName,
            metaKey
        });

        // Click to upload
        uploadArea?.addEventListener('click', (e) => {
            if (e.target.closest('.media-item')) return;
            fileInput?.click();
        });

        // File input change
        fileInput?.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        // Drag and drop
        uploadArea?.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea?.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea?.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        // Media item actions (delete and edit)
        grid?.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]');
            if (!action) return;

            const mediaItem = action.closest('.media-item');
            const mediaId = mediaItem?.dataset.mediaId;

            if (action.dataset.action === 'delete') {
                e.stopPropagation(); // Prevent bubbling to FieldSet delete handler
                deleteMedia(mediaItem, mediaId);
            } else if (action.dataset.action === 'edit') {
                e.stopPropagation(); // Prevent bubbling
                editMedia(mediaItem, mediaId);
            }
        });

        // Initialize Sortable for drag-to-reorder (if multiple files allowed)
        if (multiple && grid) {
            Sortable.create(grid, {
                animation: 400,
                easing: 'cubic-bezier(0.25, 0.8, 0.25, 1)',
                handle: '.media-drag-handle',
                onEnd: () => {
                    updateMediaNumbers();
                    updateMediaOrder();
                }
            });
        }

        function handleFiles(files) {
            const filesArray = Array.from(files);

            // Check max files
            if (!multiple) {
                if (filesArray.length > 1) {
                    showToast('warning', 'You can only upload 1 file');
                    return;
                }
                // Remove existing if single file mode - DELETE immediately from server
                const existing = grid.querySelectorAll('.media-item');
                existing.forEach(item => {
                    const id = item.dataset.mediaId;
                    if (id) {
                        // Delete from server immediately (without confirm for replacement)
                        deleteMediaFromServer(id, item);
                    } else {
                        // Just remove from DOM if not yet saved
                        item.remove();
                    }
                });
            } else if (maxFiles) {
                const currentCount = grid.querySelectorAll('.media-item').length;
                if (currentCount + filesArray.length > maxFiles) {
                    showToast('warning', `Maximum ${maxFiles} files allowed`);
                    return;
                }
            }

            // Validate and upload files
            for (const file of filesArray) {
                if (maxSize && file.size > maxSize * 1024) {
                    showToast('warning', `File "${file.name}" is too large. Maximum size: ${(maxSize / 1024).toFixed(1)} MB`);
                    continue;
                }
                uploadFile(file);
            }

            // Reset input
            if (fileInput) {
                fileInput.value = '';
            }
        }

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('files[]', file);
            formData.append('collection', collection);
            if (modelType) {
                formData.append('model_type', modelType);
            }
            if (modelId) {
                formData.append('model_id', modelId);
            }
            if (maxSize) {
                formData.append('max_size', maxSize);
            }

            // Show progress
            const progressBar = uploadArea?.querySelector('.upload-progress');
            const progressFill = progressBar?.querySelector('.progress-fill');
            const progressText = progressBar?.querySelector('.progress-text');
            const uploadPrompt = uploadArea?.querySelector('.upload-prompt');

            if (uploadPrompt) uploadPrompt.style.display = 'none';
            if (progressBar) {
                progressBar.style.display = 'block';
                progressBar.classList.add('active');
            }

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && progressFill && progressText) {
                    const percent = (e.loaded / e.total) * 100;
                    progressFill.style.width = percent + '%';
                    progressText.textContent = `Uploading... ${Math.round(percent)}%`;
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success && response.media) {
                            response.media.forEach(media => {
                                addMediaItem(media);
                                uploadedIds.push(media.id);
                            });
                            updateHiddenInputs();
                            showToast('success', 'File uploaded successfully.');
                        } else {
                            showToast('danger', 'Upload failed: ' + (response.message || 'Unknown error'));
                        }
                    } catch (e) {
                        showToast('danger', 'Upload failed: Invalid server response');
                    }
                } else {
                    showToast('danger', 'Upload failed: ' + xhr.statusText);
                }

                // Reset progress
                if (progressBar) {
                    progressBar.classList.remove('active');
                    progressBar.style.display = 'none';
                }
                if (uploadPrompt) uploadPrompt.style.display = 'flex';
                if (progressFill) progressFill.style.width = '0%';
            });

            xhr.addEventListener('error', () => {
                showToast('danger', 'Upload failed. Please try again.');
                if (progressBar) {
                    progressBar.classList.remove('active');
                    progressBar.style.display = 'none';
                }
                if (uploadPrompt) uploadPrompt.style.display = 'flex';
            });

            xhr.open('POST', uploadUrl, true);
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            }
            xhr.send(formData);
        }

        function addMediaItem(media) {
            const template = document.getElementById('media-item-template-' + fieldName);
            if (!template) {
                console.error('Media item template not found:', 'media-item-template-' + fieldName);
                return;
            }

            const item = template.content.cloneNode(true).querySelector('.media-item');
            item.dataset.mediaId = media.id;

            const isImage = media.mime_type && media.mime_type.startsWith('image/');

            if (isImage) {
                const img = item.querySelector('.media-image');
                img.src = media.url || media.preview_url;
                img.alt = media.file_name;
                img.style.display = 'block';
            } else {
                const fileIcon = item.querySelector('.media-file-icon');
                const fileName = fileIcon.querySelector('.media-file-name');
                fileName.textContent = media.file_name;
                fileIcon.style.display = 'flex';
            }

            const hiddenInput = item.querySelector('input[type="hidden"]');
            hiddenInput.value = media.id;

            // Fill footer information
            const footer = item.querySelector('.media-item-footer');
            if (footer) {
                // File name
                const filenameEl = footer.querySelector('.media-filename');
                if (filenameEl) {
                    filenameEl.textContent = media.file_name;
                }

                // File size
                const sizeEl = footer.querySelector('.media-size');
                if (sizeEl && media.size) {
                    sizeEl.textContent = humanFileSize(media.size);
                    // Add class for large files (> 1MB)
                    if (media.size > 1048576) {
                        sizeEl.classList.add('media-large-file');
                    }
                }

                // Title from props
                const titleEl = footer.querySelector('.media-title i');
                if (titleEl && media.title) {
                    titleEl.textContent = media.title;
                }
            }

            grid?.appendChild(item);

            // Emit event for potential component reinitialization
            // Allows other modules to react to media gallery updates
            aveEvents.emit('dom:updated', item);

            // Update all media numbers
            updateMediaNumbers();
        }

        /**
         * Delete media from server (without confirmation)
         * Used for automatic replacement in single file mode
         */
        function deleteMediaFromServer(mediaId, mediaItem) {
            if (!mediaId) {
                mediaItem?.remove();
                updateMediaNumbers();
                return;
            }

            const deleteUrl = uploadUrl.replace('/upload', `/${mediaId}`);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mediaItem?.remove();
                    updateMediaNumbers();
                } else {
                    console.error('Failed to delete file:', data.message);
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
            });
        }

        async function deleteMedia(mediaItem, mediaId) {
            const fileName = mediaItem?.querySelector('.media-filename')?.textContent || 'this file';

            const confirmed = await confirm('You are going to remove:', {
                title: 'Delete File',
                variant: 'error',
                bodyParams: [fileName],
                confirmText: 'Delete',
                cancelText: 'Cancel'
            });

            if (!confirmed) return;

            // If no mediaId (newly uploaded, not saved yet), just remove from DOM
            if (!mediaId) {
                mediaItem?.remove();
                updateMediaNumbers();
                showToast('success', 'File removed successfully.');
                return;
            }

            // Delete from server immediately
            const deleteUrl = uploadUrl.replace('/upload', `/${mediaId}`);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mediaItem?.remove();
                    updateMediaNumbers();
                    showToast('success', 'File deleted successfully.');
                } else {
                    showToast('danger', 'Failed to delete file: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                showToast('danger', 'Failed to delete file. Please try again.');
            });
        }

        function editMedia(mediaItem, mediaId) {
            const fileName = mediaItem?.querySelector('.media-filename')?.textContent || 'Media';

            // Get current props from hidden input
            const propsInput = mediaItem.querySelector(`input[data-media-props="true"][data-props-id="${mediaId}"]`);
            const currentProps = propsInput ? JSON.parse(propsInput.value || '{}') : {};

            // Generate form fields dynamically from propNames
            const formFields = propNames.map(propName => {
                const label = propName.charAt(0).toUpperCase() + propName.slice(1).replace(/_/g, ' ');
                const inputId = `media-prop-${propName}`;
                return `
                    <div class="form-field">
                        <label for="${inputId}">${label}</label>
                        <input type="text" id="${inputId}" name="${propName}" class="form-control" value="${currentProps[propName] || ''}" placeholder="Enter ${label.toLowerCase()}">
                    </div>
                `;
            }).join('');

            // Create modal with simple text inputs
            const modal = createModal({
                title: `Edit: ${fileName}`,
                body: formFields,
                type: 'form',
                confirmText: 'Save',
                cancelText: 'Cancel',
                size: 'default',
                autoClose: false,
                onConfirm: (modalElement) => {
                    saveMediaProps(modalElement, mediaId, mediaItem);
                }
            });
        }

        function saveMediaProps(modalElement, mediaId, mediaItem) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const props = {};

            // Collect form data from simple text inputs
            modalElement.querySelectorAll('input[name], textarea[name]').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    props[name] = input.value || '';
                }
            });

            // Send AJAX request to save props as JSON
            const baseUrl = uploadUrl.replace('/upload', '');

            fetch(`${baseUrl}/${mediaId}/props`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(props)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update title display if props contain title
                    if (data.media?.props?.title) {
                        const titleDisplay = mediaItem.querySelector('.media-title i');
                        if (titleDisplay) {
                            titleDisplay.textContent = data.media.props.title;
                        }
                    }

                    // Store props in hidden input for future edits
            const currentMetaKey = container.dataset.metaKey || metaKey || computeMetaKey(fieldName);
            let propsInput = mediaItem.querySelector(`input[data-media-props="true"][data-props-id="${mediaId}"]`);
            if (!propsInput) {
                propsInput = document.createElement('input');
                propsInput.type = 'hidden';
                propsInput.name = `__media_props[${currentMetaKey}][${mediaId}]`;
                propsInput.setAttribute('data-media-props', 'true');
                propsInput.setAttribute('data-props-id', mediaId);
                mediaItem.appendChild(propsInput);
            } else {
                propsInput.name = `__media_props[${currentMetaKey}][${mediaId}]`;
                propsInput.setAttribute('data-media-props', 'true');
                propsInput.setAttribute('data-props-id', mediaId);
            }
                    propsInput.value = JSON.stringify(data.media.props);

                    showToast('success', data.message || 'Properties saved successfully');
                    destroyModal(modalElement);
                } else {
                    throw new Error(data.message || 'Save failed');
                }
            })
            .catch(error => {
                showToast('danger', 'Failed to save: ' + error.message);
            });
        }

        function updateHiddenInputs() {
            if (uploadedIdsInput) {
                uploadedIdsInput.value = uploadedIds.join(',');
                console.debug('Media uploaded IDs updated', {
                    fieldName,
                    metaKey,
                    uploadedIds: [...uploadedIds],
                    inputName: uploadedIdsInput.name,
                });
            }
            if (deletedIdsInput) {
                deletedIdsInput.value = deletedIds.join(',');
            }
        }

        function updateMediaNumbers() {
            const mediaItems = grid?.querySelectorAll('.media-item');
            mediaItems?.forEach((item, index) => {
                const orderBadge = item.querySelector('.media-order');
                if (orderBadge) {
                    orderBadge.textContent = index + 1;
                }
            });
        }

        function updateMediaOrder() {
            // Get all media items in current order
            const mediaItems = grid.querySelectorAll('.media-item');
            const orderData = [];

            mediaItems.forEach((item, index) => {
                const mediaId = item.dataset.mediaId;
                if (mediaId) {
                    orderData.push({
                        id: parseInt(mediaId),
                        order: index
                    });
                }
            });

            if (orderData.length === 0) {
                return;
            }

            // Send batch update to server
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const updateUrl = uploadUrl.replace('/upload', '/reorder');

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    media: orderData,
                    collection: collection
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Files reordered successfully.');
                } else {
                    console.error('Order update failed:', data.message);
                    showToast('danger', 'Failed to reorder files.');
                }
            })
            .catch(error => {
                console.error('Order update error:', error);
                showToast('danger', 'Failed to reorder files.');
            });
        }
    });
}

