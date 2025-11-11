/**
 * Unified handler for Ave resource actions (row, bulk, global, form).
 */

import { createModal, destroyModal } from '../ui/modals.js';
import { showToast } from '../ui/toast.js';

const ACTION_SELECTOR = '[data-ave-action]';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

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
        showToast('warning', 'Выберите хотя бы одну запись.');
        return;
    }

    const config = parseConfig(trigger.dataset.actionConfig);
    const confirmMessage = trigger.dataset.actionConfirmMessage;

    const run = async (extraPayload = {}) => {
        try {
            trigger.dataset.actionLoading = 'true';
            trigger.classList.add('is-loading');
            await executeAction(trigger, endpoint, extraPayload);
        } finally {
            trigger.dataset.actionLoading = 'false';
            trigger.classList.remove('is-loading');
        }
    };

    if (Array.isArray(config.form) && config.form.length > 0) {
        openFormModal(trigger, config, run);
        return;
    }

    if (trigger.dataset.actionConfirm === 'true') {
        createModal({
            title: config.label || 'Подтвердите действие',
            body: confirmMessage || 'Вы уверены, что хотите выполнить действие?',
            type: 'confirm',
            variant: trigger.dataset.variant || 'primary',
            confirmText: config.label || 'Выполнить',
            onConfirm: () => run(),
        });
        return;
    }

    run();
}

async function executeAction(trigger, endpoint, extraPayload = {}) {
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
        const message = data.message || 'Ошибка при выполнении действия.';
        showToast('danger', message);
        throw new Error(message);
    }

    const message = data.message || 'Действие выполнено.';
    showToast('success', message);

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
        title: config.label || 'Параметры действия',
        body: renderActionForm(formSchema),
        type: 'form',
        variant: trigger.dataset.variant || 'primary',
        confirmText: config.label || 'Выполнить',
        autoClose: false,
        onConfirm: async () => {
            const modalForm = modal.querySelector('[data-ave-action-form]');
            const modalData = collectModalFormData(modalForm);

            await runCallback(modalData);
            destroyModal(modal);
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
