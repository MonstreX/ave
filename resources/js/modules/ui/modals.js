import { aveEvents } from '../../core/EventBus.js';
import { trans } from '../../utils/translations.js';

const openModal = (modal) => {
    if (!modal) {
        return;
    }
    modal.classList.add('is-active');
    modal.setAttribute('aria-hidden', 'false');
    const firstFocusable = modal.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    firstFocusable?.focus();
};

export const closeModal = (modal) => {
    if (!modal) {
        return;
    }
    modal.classList.remove('is-active');
    modal.setAttribute('aria-hidden', 'true');
};

/**
 * Close and remove modal from DOM
 */
export const destroyModal = (modal) => {
    if (!modal) {
        return;
    }
    closeModal(modal);
    setTimeout(() => modal.remove(), 300);
};

/**
 * Create and show a dynamic modal
 * @param {Object} options - Modal configuration
 * @param {string} options.title - Modal title
 * @param {string} options.body - Modal body content (HTML or text)
 * @param {Array} [options.bodyParams] - Array of parameters to highlight in white (optional)
 * @param {string} [options.type='default'] - Modal type: 'confirm', 'alert', 'form', 'default'
 * @param {string} [options.variant='default'] - Modal variant: 'success', 'error', 'warning', 'info', 'default'
 * @param {string} [options.confirmText='OK'] - Confirm button text
 * @param {string} [options.cancelText='Cancel'] - Cancel button text
 * @param {Function} [options.onConfirm] - Callback when confirmed
 * @param {Function} [options.onCancel] - Callback when cancelled
 * @param {string} [options.size='default'] - Modal size: 'small', 'default', 'large'
 * @param {boolean} [options.autoClose=true] - Auto close modal after confirm (set false for forms)
 * @returns {HTMLElement} - The created modal element
 */
export const createModal = (options) => {
    const {
        title = 'Modal',
        body = '',
        bodyParams = null,
        type = 'default',
        variant = 'default',
        confirmText = 'OK',
        cancelText = 'Cancel',
        onConfirm = null,
        onCancel = null,
        size = 'default',
        autoClose = true
    } = options;

    // Create modal structure
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.setAttribute('data-ave-modal', '');
    modal.setAttribute('data-modal-dynamic', 'true');

    const sizeClass = size === 'small' ? 'modal-sm' : size === 'large' ? 'modal-lg' : '';
    const variantClass = variant !== 'default' ? `modal-${variant}` : '';

    // Format body with params if provided
    let formattedBody = body;
    if (bodyParams && Array.isArray(bodyParams) && bodyParams.length > 0) {
        const paramsHtml = bodyParams.map(param => `<span class="modal-param">"${param}"</span>`).join(', ');
        formattedBody = `<p class="modal-message">${body} ${paramsHtml}</p>`;
    } else if (body && !body.startsWith('<')) {
        formattedBody = `<p>${body}</p>`;
    }

    modal.innerHTML = `
        <div class="modal-background" data-ave-modal-close></div>
        <div class="modal-dialog ${sizeClass} ${variantClass}">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">${title}</h4>
                    <button class="close" data-ave-modal-dismiss type="button">&times;</button>
                </div>
                <div class="modal-body">
                    ${formattedBody}
                </div>
                <div class="modal-footer">
                    ${type === 'alert' ? `
                        <button class="btn btn-modal-confirm" data-modal-confirm type="button">${confirmText}</button>
                    ` : type === 'confirm' ? `
                        <button class="btn btn-default" data-modal-cancel type="button">${cancelText}</button>
                        <button class="btn btn-modal-confirm" data-modal-confirm type="button">${confirmText}</button>
                    ` : type === 'form' ? `
                        <button class="btn btn-default" data-modal-cancel type="button">${cancelText}</button>
                        <button class="btn btn-modal-confirm" data-modal-confirm type="button">${confirmText}</button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;

    // Add event listeners
    const confirmBtn = modal.querySelector('[data-modal-confirm]');
    const cancelBtn = modal.querySelector('[data-modal-cancel]');
    const dismissBtn = modal.querySelector('[data-ave-modal-dismiss]');
    const background = modal.querySelector('[data-ave-modal-close]');

    const cleanup = () => {
        closeModal(modal);
        setTimeout(() => modal.remove(), 300);
    };

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (onConfirm) {
                onConfirm(modal);
            }
            if (autoClose) {
                cleanup();
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (onCancel) {
                onCancel();
            }
            cleanup();
        });
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', cleanup);
    }

    if (background) {
        background.addEventListener('click', () => {
            if (onCancel) {
                onCancel();
            }
            cleanup();
        });
    }

    // Append to body and open
    document.body.appendChild(modal);
    requestAnimationFrame(() => {
        openModal(modal);
        // Emit event for form component reinitialization
        // This allows form components in modal to be properly initialized
        aveEvents.emit('dom:updated', modal);
    });

    return modal;
};

/**
 * Show a confirmation dialog
 * @param {string} message - Confirmation message
 * @param {Object} options - Additional options
 * @param {Array} [options.bodyParams] - Array of parameters to highlight
 * @returns {Promise<boolean>} - Resolves to true if confirmed, false if cancelled
 */
export const confirm = (message, options = {}) => {
    return new Promise((resolve) => {
        createModal({
            title: options.title || trans('common.confirm'),
            body: message,
            bodyParams: options.bodyParams || null,
            type: 'confirm',
            variant: options.variant || 'default',
            confirmText: options.confirmText || trans('common.yes'),
            cancelText: options.cancelText || trans('common.cancel'),
            size: options.size || 'small',
            onConfirm: () => resolve(true),
            onCancel: () => resolve(false)
        });
    });
};

/**
 * Show an alert dialog
 * @param {string} message - Alert message
 * @param {Object} options - Additional options
 * @param {Array} [options.bodyParams] - Array of parameters to highlight
 */
export const alert = (message, options = {}) => {
    createModal({
        title: options.title || trans('common.alert'),
        body: message,
        bodyParams: options.bodyParams || null,
        type: 'alert',
        variant: options.variant || 'default',
        confirmText: options.confirmText || trans('common.ok'),
        size: options.size || 'small'
    });
};

export default function initModals() {
    // Expose modal functions to window for global access
    window.Ave = window.Ave || {};
    window.Ave.confirm = confirm;
    window.Ave.createModal = createModal;
    window.Ave.closeModal = closeModal;
    window.Ave.destroyModal = destroyModal;

    document.querySelectorAll('[data-ave-modal-trigger]').forEach((trigger) => {
        const targetSelector = trigger.getAttribute('data-ave-modal-trigger');
        const targetModal = targetSelector ? document.querySelector(targetSelector) : null;

        if (!targetModal) {
            return;
        }

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(targetModal);
        });
    });

    document.querySelectorAll('[data-ave-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target.hasAttribute('data-ave-modal-close')) {
                closeModal(modal);
            }
        });

        modal.querySelectorAll('[data-ave-modal-dismiss]').forEach((dismissBtn) => {
            dismissBtn.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal(modal);
            });
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const activeModal = document.querySelector('[data-ave-modal].is-active');
            if (activeModal) {
                closeModal(activeModal);
            }
        }
    });
}



