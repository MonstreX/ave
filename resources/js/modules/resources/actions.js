/**
 * Unified handler for Ave resource actions (row, bulk, global, form).
 */

import { createModal, destroyModal } from '../ui/modals.js';
import { showToast } from '../ui/toast.js';
import { openPopupForm } from '../forms/popupForm.js';
import { trans } from '../../utils/translations.js';

const ACTION_SELECTOR = '[data-ave-action]';
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

function disableTrigger(trigger) {
    trigger.dataset.actionLoading = 'true';
    trigger.classList.add('is-loading');
    trigger.setAttribute('aria-busy', 'true');

    if (trigger.tagName === 'BUTTON' || trigger.tagName === 'INPUT') {
        trigger.disabled = true;
    }
}

function releaseTrigger(trigger) {
    trigger.dataset.actionLoading = 'false';
    trigger.classList.remove('is-loading');
    trigger.removeAttribute('aria-busy');

    if (trigger.tagName === 'BUTTON' || trigger.tagName === 'INPUT') {
        trigger.disabled = false;
    }
}

export default function initResourceActions() {
    setupBulkSelection();
    document.addEventListener('click', handleActionClick, true);
}

function handleActionClick(event) {
    const trigger = event.target.closest(ACTION_SELECTOR);
    if (!trigger) {
        return;
    }

    event.preventDefault();

    if (trigger.dataset.actionLoading === 'true') {
        return;
    }

    const actionType = trigger.dataset.aveAction;
    const endpoint = trigger.dataset.actionEndpoint;

    if (!endpoint) {
        console.warn('Ave action missing endpoint', trigger);
        return;
    }

    if (actionType === 'bulk' && getSelectedIds().length === 0) {
        showToast('warning', trans('actions.no_selection'));
        return;
    }

    const config = parseConfig(trigger.dataset.actionConfig);
    const confirmMessage = trigger.dataset.actionConfirmMessage;

    const run = async (extraPayload = {}, meta = {}) => {
        disableTrigger(trigger);
        try {
            await executeAction(trigger, endpoint, extraPayload, meta);
        } finally {
            releaseTrigger(trigger);
        }
    };

    if (Array.isArray(config.form) && config.form.length > 0) {
        openFormModal(trigger, config, run);
        return;
    }

    if (trigger.dataset.actionConfirm === 'true') {
        createModal({
            title: config.label || trans('actions.confirm'),
            body: confirmMessage || trans('actions.confirm'),
            type: 'confirm',
            variant: trigger.dataset.variant || 'primary',
            confirmText: config.label || trans('common.confirm'),
            onConfirm: () => run(),
        });
        return;
    }

    run();
}

async function executeAction(trigger, endpoint, extraPayload = {}, meta = {}) {
    const actionType = trigger.dataset.aveAction;
    const payload = new FormData();
    const method = trigger.dataset.actionMethod || 'POST';
    const accept = trigger.dataset.actionAccept || 'application/json';

    if (CSRF_TOKEN && !payload.has('_token')) {
        payload.append('_token', CSRF_TOKEN);
    }

    payload.append('_action', trigger.dataset.aveActionKey || '');

    if (actionType === 'bulk') {
        getSelectedIds().forEach((id) => payload.append('ids[]', id));
    }

    if (actionType === 'form') {
        appendFormValues(
            payload,
            trigger.dataset.actionFormSelector || '#ave-resource-form'
        );
    }

    // For global actions, pass current URL query params to action
    // This allows actions to access context like menu_id, parent_id, etc.
    if (actionType === 'global') {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.forEach((value, key) => {
            payload.append(key, value);
        });
    }

    appendExtraPayload(payload, extraPayload);

    const response = await fetch(endpoint, {
        method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': accept,
            'X-CSRF-TOKEN': CSRF_TOKEN,
        },
        body: payload,
    });

    const data = await parseResponse(response);

    if (!response.ok) {
        const message = extractErrorMessage(response, data);

        if (meta && meta.modal) {
            showModalErrors(meta.modal, data?.errors ?? {}, message);
        }

        showToast('danger', message);
        throw new Error(message);
    }

    if (meta && meta.modal) {
        clearModalErrors(meta.modal);
    }

    // Check if action wants to open a modal form
    if (data.modal_form === true) {
        openPopupForm({
            title: data.title || 'Form',
            fetchUrl: data.fetch_url,
            saveUrl: data.save_url,
            size: data.size || 'large',
            onSuccess: (responseData) => {
                if (responseData.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            }
        });
        return;
    }

    // Only show toast if action explicitly provides a message
    if (data.message) {
        showToast('success', data.message);
    }

    const redirect = data.redirect ?? null;
    if (redirect) {
        window.location.href = redirect;
        return;
    }

    if (data.reload !== false) {
        window.dispatchEvent(
            new CustomEvent('ave:action:completed', {
                detail: {
                    type: actionType,
                    key: trigger.dataset.aveActionKey,
                    result: data.result ?? null,
                },
            })
        );

        // Даем тосту показаться, потом перезагружаем таблицу.
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
}

function parseConfig(value) {
    if (!value) {
        return {};
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        console.warn('Failed to parse action config', error);
        return {};
    }
}

function appendFormValues(payload, selector) {
    if (!selector) {
        return;
    }

    const form = document.querySelector(selector);
    if (!form) {
        return;
    }

    const formData = new FormData(form);
    formData.forEach((value, key) => {
        payload.append(key, value);
    });
}

function appendExtraPayload(payload, extraPayload) {
    if (!extraPayload || typeof extraPayload !== 'object') {
        return;
    }

    Object.entries(extraPayload).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item) => payload.append(`${key}[]`, item));
            return;
        }

        payload.append(key, value);
    });
}

async function parseResponse(response) {
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
        return response.json();
    }

    const text = await response.text();
    return {
        message: text,
    };
}

function extractErrorMessage(response, payload) {
    if (response.status === 422 && payload?.errors) {
        return formatValidationErrors(payload.errors);
    }

    if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
        return payload.message;
    }

    return response.statusText || FALLBACK_VALIDATION_MESSAGE;
}

function formatValidationErrors(errors) {
    if (!errors || typeof errors !== 'object') {
        return FALLBACK_VALIDATION_MESSAGE;
    }

    const messages = Object.values(errors)
        .flatMap((entry) => (Array.isArray(entry) ? entry : [entry]))
        .map((message) => (message ?? '').toString().trim())
        .filter((message) => message.length > 0);

    if (messages.length === 0) {
        return FALLBACK_VALIDATION_MESSAGE;
    }

    const limited = messages.slice(0, 3);
    if (messages.length > limited.length) {
        limited.push(`… +${messages.length - limited.length} ещё`);
    }

    return limited.join('\n');
}

function clearModalErrors(modal) {
    const form = modal.querySelector('[data-ave-action-form]');
    if (!form) {
        return;
    }

    form.querySelectorAll('.form-error').forEach((node) => node.remove());
    form.querySelectorAll('.form-group.has-error').forEach((group) => {
        group.classList.remove('has-error');
    });
}

function showModalErrors(modal, errors = {}, fallbackMessage = '') {
    const form = modal.querySelector('[data-ave-action-form]');
    if (!form) {
        return;
    }

    clearModalErrors(modal);

    const entries = Object.entries(errors);
    if (entries.length === 0) {
        if (fallbackMessage) {
            const generalError = document.createElement('div');
            generalError.className = 'form-error text-danger small';
            generalError.textContent = fallbackMessage;
            form.prepend(generalError);
        }
        return;
    }

    entries.forEach(([field, fieldErrors]) => {
        const selector = `[name=\"${escapeSelector(field)}\"]`;
        const inputs = form.querySelectorAll(selector);

        if (inputs.length === 0) {
            return;
        }

        const messages = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];
        const messageText = messages
            .map((item) => (item ?? '').toString().trim())
            .filter(Boolean)
            .join(' ') || FALLBACK_VALIDATION_MESSAGE;

        inputs.forEach((input) => {
            const group = input.closest('.form-group') || input.parentElement;
            if (!group) {
                return;
            }

            group.classList.add('has-error');

            const errorEl = document.createElement('div');
            errorEl.className = 'form-error text-danger small';
            errorEl.textContent = messageText;
            group.appendChild(errorEl);
        });
    });

    const firstErrorInput = form.querySelector(
        '.form-group.has-error input, .form-group.has-error select, .form-group.has-error textarea'
    );
    if (firstErrorInput) {
        firstErrorInput.focus();
    }
}

function escapeSelector(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(value);
    }

    return value.replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\\\$1');
}

function setupBulkSelection() {
    const bulkToolbar = document.getElementById('bulk-actions-toolbar');
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = Array.from(document.querySelectorAll('.row-selector'));
    const countSpan = document.getElementById('selected-count');

    if (!bulkToolbar || rowCheckboxes.length === 0) {
        return;
    }

    const updateToolbar = () => {
        const selected = rowCheckboxes.filter((cb) => cb.checked);
        if (countSpan) {
            countSpan.textContent = selected.length;
        }

        bulkToolbar.classList.toggle('is-visible', selected.length > 0);

        if (selectAllCheckbox) {
            const allChecked = selected.length === rowCheckboxes.length;
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate =
                selected.length > 0 && !allChecked;
        }
    };

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (event) => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = event.target.checked;
                toggleRowHighlight(checkbox);
            });
            updateToolbar();
        });
    }

    rowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            toggleRowHighlight(checkbox);
            updateToolbar();
        });

        toggleRowHighlight(checkbox);
    });

    updateToolbar();
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.row-selector:checked')).map(
        (checkbox) => checkbox.value
    );
}

function toggleRowHighlight(checkbox) {
    const row = checkbox.closest('.resource-row');
    if (row) {
        row.classList.toggle('is-selected', checkbox.checked);
    }
}

function openFormModal(trigger, config, runCallback) {
    const formSchema = Array.isArray(config.form) ? config.form : [];
    const modal = createModal({
        title: config.label || trans('actions.confirm'),
        body: renderActionForm(formSchema),
        type: 'form',
        variant: trigger.dataset.variant || 'primary',
        confirmText: config.label || trans('common.confirm'),
        autoClose: false,
        onConfirm: async () => {
            const modalForm = modal.querySelector('[data-ave-action-form]');
            const modalData = collectModalFormData(modalForm);

            try {
                await runCallback(modalData, { modal });
                destroyModal(modal);
            } catch (error) {
                console.warn('Ave action failed', error);
            }
        },
        onCancel: () => destroyModal(modal),
    });

    return modal;
}

function renderActionForm(schema) {
    const fields = schema
        .map((field) => {
            const name = field.name || field.key || '';
            if (!name) {
                return '';
            }

            const label = field.label || name;
            const type = (field.type || 'text').toLowerCase();
            const required = field.required ? 'required' : '';
            const placeholder = field.placeholder || '';
            const help = field.help ? `<small class="help-block">${field.help}</small>` : '';

            if (type === 'textarea') {
                return `
                    <div class="form-group">
                        <label class="control-label">${label}</label>
                        <textarea class="form-control" name="${name}" ${required} placeholder="${placeholder}"></textarea>
                        ${help}
                    </div>
                `;
            }

            if (type === 'select' && Array.isArray(field.options)) {
                const options = field.options
                    .map(
                        (option) =>
                            `<option value="${option.value}">${option.label}</option>`
                    )
                    .join('');

                return `
                    <div class="form-group">
                        <label class="control-label">${label}</label>
                        <select class="form-control" name="${name}" ${required}>
                            ${options}
                        </select>
                        ${help}
                    </div>
                `;
            }

            if (type === 'checkbox') {
                return `
                    <div class="form-group">
                        <label class="control-label">
                            <input type="checkbox" name="${name}" value="1" />
                            ${label}
                        </label>
                        ${help}
                    </div>
                `;
            }

            return `
                <div class="form-group">
                    <label class="control-label">${label}</label>
                    <input type="${type}" class="form-control" name="${name}" ${required} placeholder="${placeholder}" />
                    ${help}
                </div>
            `;
        })
        .join('');

    return `
        <form data-ave-action-form>
            ${fields}
        </form>
    `;
}

function collectModalFormData(form) {
    if (!form) {
        return {};
    }

    const formData = new FormData(form);
    const result = {};

    formData.forEach((value, key) => {
        if (result[key] !== undefined) {
            if (!Array.isArray(result[key])) {
                result[key] = [result[key]];
            }
            result[key].push(value);
        } else {
            result[key] = value;
        }
    });

    return result;
}
