import { createModal, closeModal } from '../ui/modals.js';
import { showToast } from '../ui/toast.js';
import { aveEvents } from '../../core/EventBus.js';
import { trans } from '../../utils/translations.js';

/**
 * Universal Popup Form Handler
 *
 * Provides AJAX-based modal forms with server-side rendering and validation.
 *
 * Usage:
 * openPopupForm({
 *     title: 'Edit Properties',
 *     fetchUrl: '/api/resource/123/form',
 *     saveUrl: '/api/resource/123',
 *     onSuccess: (data) => { console.log('Saved!', data); }
 * });
 */

/**
 * Open a popup form with server-rendered fields
 *
 * @param {Object} options Configuration options
 * @param {string} options.title Modal title
 * @param {string} options.fetchUrl URL to fetch form HTML
 * @param {string} options.saveUrl URL to save form data
 * @param {Object} [options.fetchParams] Additional params for fetch request
 * @param {Function} [options.onSuccess] Callback on successful save
 * @param {Function} [options.onError] Callback on error
 * @param {string} [options.confirmText] Save button text (default: 'Save')
 * @param {string} [options.cancelText] Cancel button text (default: 'Cancel')
 * @param {string} [options.size] Modal size: 'small', 'default', 'large' (default: 'default')
 */
export function openPopupForm(options) {
    const {
        title,
        fetchUrl,
        saveUrl,
        fetchParams = {},
        onSuccess = null,
        onError = null,
        confirmText = trans('common.save'),
        cancelText = trans('common.cancel'),
        size = 'default'
    } = options;

    // Show loading modal
    const loadingModal = createModal({
        title: trans('common.loading'),
        body: `<div class="spinner">${trans('common.loading_form')}</div>`,
        type: 'alert',
        size: 'small',
    });

    // Fetch form HTML from server
    fetchFormHtml(fetchUrl, fetchParams)
        .then(({ formHtml, currentData }) => {
            closeModal(loadingModal);

            // Show form modal
            const formModal = createModal({
                title: title,
                body: formHtml,
                type: 'form',
                size: size,
                confirmText: confirmText,
                cancelText: cancelText,
                onConfirm: (modalElement) => {
                    savePopupForm(modalElement, saveUrl, onSuccess, onError);
                }
            });

            // Emit event for form component reinitialization
            // The modals.js will also emit dom:updated, but we emit again
            // after form HTML is rendered to ensure form components are initialized
            aveEvents.emit('dom:updated', formModal);
        })
        .catch(error => {
            closeModal(loadingModal);
            showToast('danger', trans('common.failed_to_load_form', { error: error.message }));

            if (onError) {
                onError(error);
            }
        });
}

/**
 * Fetch form HTML from server
 *
 * @param {string} url Fetch URL
 * @param {Object} params Additional parameters
 * @return {Promise<{formHtml: string, currentData: Object}>}
 */
function fetchFormHtml(url, params = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Build query string from params
    const queryParams = new URLSearchParams(params).toString();
    const fetchUrl = queryParams ? `${url}?${queryParams}` : url;

    return fetch(fetchUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to load form');
        }

        return {
            formHtml: data.formHtml || '',
            currentData: data.currentData || {},
        };
    });
}

/**
 * Save popup form data to server
 *
 * @param {HTMLElement} modalElement Modal element containing the form
 * @param {string} saveUrl Save URL
 * @param {Function|null} onSuccess Success callback
 * @param {Function|null} onError Error callback
 */
function savePopupForm(modalElement, saveUrl, onSuccess = null, onError = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const formData = new FormData();

    // Collect data from all form inputs in modal
    modalElement.querySelectorAll('input, textarea, select').forEach(input => {
        if (input.name) {
            if (input.type === 'checkbox') {
                formData.append(input.name, input.checked ? '1' : '0');
            } else if (input.type === 'radio') {
                if (input.checked) {
                    formData.append(input.name, input.value);
                }
            } else {
                formData.append(input.name, input.value);
            }
        }
    });

    // Send to server
    fetch(saveUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: formData
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
            showToast('success', data.message || trans('common.saved_successfully'));

            if (onSuccess) {
                onSuccess(data);
            }

            closeModal(modalElement);
        } else {
            throw new Error(data.message || 'Save failed');
        }
    })
    .catch(error => {
        showToast('danger', trans('common.failed_to_save', { error: error.message }));

        if (onError) {
            onError(error);
        }
    });
}

/**
 * Create a popup form for inline data editing
 *
 * Simplified version that works with inline data (no fetch needed)
 *
 * @param {Object} options Configuration options
 * @param {string} options.title Modal title
 * @param {Array} options.fields Field definitions
 * @param {Object} [options.data] Initial data
 * @param {Function} options.onSave Callback with form data
 */
export function createInlinePopupForm(options) {
    const {
        title,
        fields,
        data = {},
        onSave,
        confirmText = trans('common.save'),
        cancelText = trans('common.cancel'),
        size = 'default'
    } = options;

    // Generate simple form HTML from field definitions
    let formHtml = '<div class="popup-form-fields">';

    fields.forEach(field => {
        const value = data[field.name] || '';
        const label = field.label || field.name;
        const required = field.required ? 'required' : '';

        if (field.type === 'textarea') {
            formHtml += `
                <div class="form-field">
                    <label class="form-label">${label}${field.required ? ' <span class="required">*</span>' : ''}</label>
                    <textarea name="${field.name}" class="form-control" rows="${field.rows || 3}" ${required}>${value}</textarea>
                </div>
            `;
        } else if (field.type === 'select') {
            let optionsHtml = '';
            Object.entries(field.options || {}).forEach(([val, text]) => {
                const selected = value === val ? 'selected' : '';
                optionsHtml += `<option value="${val}" ${selected}>${text}</option>`;
            });

            formHtml += `
                <div class="form-field">
                    <label class="form-label">${label}${field.required ? ' <span class="required">*</span>' : ''}</label>
                    <select name="${field.name}" class="form-control" ${required}>
                        <option value="">-- Select --</option>
                        ${optionsHtml}
                    </select>
                </div>
            `;
        } else {
            formHtml += `
                <div class="form-field">
                    <label class="form-label">${label}${field.required ? ' <span class="required">*</span>' : ''}</label>
                    <input type="${field.type || 'text'}" name="${field.name}" class="form-control" value="${value}" ${required}>
                </div>
            `;
        }
    });

    formHtml += '</div>';

    // Show modal
    const modal = createModal({
        title: title,
        body: formHtml,
        type: 'form',
        size: size,
        confirmText: confirmText,
        cancelText: cancelText,
        onConfirm: (modalElement) => {
            const formData = {};

            modalElement.querySelectorAll('input, textarea, select').forEach(input => {
                if (input.name) {
                    formData[input.name] = input.value;
                }
            });

            if (onSave) {
                onSave(formData);
            }

            closeModal(modalElement);
        }
    });
}

export default {
    openPopupForm,
    createInlinePopupForm,
};
