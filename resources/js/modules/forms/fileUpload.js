/**
 * File Upload Module
 * Handles file uploads with preview and progress indication
 */

export default function initFileUpload(root = document) {
    const fileFields = root.querySelectorAll('[data-file-field]');

    fileFields.forEach(wrapper => {
        const fieldKey = wrapper.dataset.fileField;
        const fileInput = wrapper.querySelector('[data-file-input]');
        const deleteBtn = wrapper.querySelector('[data-file-delete]');
        const pathInput = wrapper.querySelector('.file-path-input');
        const progressDiv = wrapper.querySelector('[data-file-progress]');
        const inputArea = wrapper.querySelector('[data-file-input-area]');
        const preview = wrapper.querySelector('.file-preview');

        if (!fileInput || !pathInput) {
            console.error('[Ave] File upload: missing required elements for field', fieldKey);
            return;
        }

        // Handle file selection
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Show progress
            progressDiv.style.display = 'block';
            const progressFill = progressDiv.querySelector('.progress-fill');
            progressFill.style.width = '0%';

            // Create FormData
            const formData = new FormData();
            formData.append('file', file);
            formData.append('field', fieldKey);

            // Add model context from parent form (if editing)
            const form = wrapper.closest('form');
            const modelType = form?.dataset.modelType || '';
            const modelId = form?.dataset.modelId || '';
            if (modelType) {
                formData.append('model_type', modelType);
            }
            if (modelId) {
                formData.append('model_id', modelId);
            }

            // Get CSRF token if available
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            // Upload file
            const xhr = new XMLHttpRequest();

            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                }
            });

            xhr.addEventListener('load', () => {
                progressDiv.style.display = 'none';

                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success && response.path) {
                            console.log('[Ave] File uploaded, path:', response.path);

                            // Update hidden input with the actual file path
                            pathInput.value = response.path;
                            console.log('[Ave] Updated hidden input value:', pathInput.value);

                            // Show preview
                            showFilePreview(wrapper, file.name, response.path);

                            // Hide file input
                            inputArea.classList.add('hidden');

                            // Reset file input
                            fileInput.value = '';
                        } else {
                            alert('Upload failed: ' + (response.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error('[Ave] Error parsing upload response:', e);
                        alert('Error processing upload response');
                    }
                } else {
                    console.error('[Ave] Upload failed with status:', xhr.status);
                    alert('Upload failed with status: ' + xhr.status);
                }
            });

            xhr.addEventListener('error', () => {
                progressDiv.style.display = 'none';
                console.error('[Ave] Upload error occurred');
                alert('Upload error occurred');
            });

            // Send request
            xhr.open('POST', '/admin/api/file-upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            }
            xhr.send(formData);
        });

        // Handle delete button
        if (deleteBtn) {
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                console.log('[Ave] File deleted from field:', fieldKey);

                // Clear path input
                pathInput.value = '';

                // Hide preview
                if (preview) {
                    preview.remove();
                }

                // Show file input
                inputArea.classList.remove('hidden');

                // Reset file input
                fileInput.value = '';
            });
        }
    });
}

/**
 * Show file preview
 */
function showFilePreview(wrapper, fileName, filePath) {
    // Remove existing preview if any
    const existingPreview = wrapper.querySelector('.file-preview');
    if (existingPreview) {
        existingPreview.remove();
    }

    // Create preview element
    const preview = document.createElement('div');
    preview.className = 'file-preview';
    preview.innerHTML = `
        <div class="file-item">
            <div class="file-info">
                <span class="file-name">${escapeHtml(fileName)}</span>
                <span class="file-size" data-file-path="${escapeHtml(filePath)}"></span>
            </div>
            <button type="button" class="file-delete-btn" data-file-delete style="cursor: pointer;">
                <i class="voyager-trash"></i> Remove
            </button>
        </div>
    `;

    // Insert before file input area
    const inputArea = wrapper.querySelector('[data-file-input-area]');
    inputArea.parentElement.insertBefore(preview, inputArea);

    // Re-attach delete handler
    const deleteBtn = preview.querySelector('[data-file-delete]');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Clear path input
            const pathInput = wrapper.querySelector('.file-path-input');
            pathInput.value = '';

            // Hide preview
            preview.remove();

            // Show file input
            const inputArea = wrapper.querySelector('[data-file-input-area]');
            inputArea.classList.remove('hidden');

            // Reset file input
            const fileInput = wrapper.querySelector('[data-file-input]');
            fileInput.value = '';
        });
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, (char) => map[char]);
}
