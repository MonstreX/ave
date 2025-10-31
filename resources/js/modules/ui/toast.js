let toastContainer = null;

/**
 * Show a toast notification
 * @param {string} type - Toast type: 'success', 'danger', 'warning', 'info'
 * @param {string} message - Toast message
 * @param {number} duration - Duration in milliseconds (default: 4000)
 */
export const showToast = (type = 'success', message = '', duration = 4000) => {
    if (!toastContainer) {
        toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            console.error('Toast container not found. Add <div id="toast-container"></div> to your layout.');
            return;
        }
    }

    const defaults = {
        success: 'Action completed successfully.',
        danger: 'Something went wrong. Please try again.',
        warning: 'Please review your action.',
        info: 'Here is some information.',
    };

    const titles = {
        success: 'Success',
        danger: 'Error',
        warning: 'Warning',
        info: 'Info',
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-title">${titles[type] || titles.info}</div>
        <p class="toast-message">${message || defaults[type] || defaults.info}</p>
    `;

    toastContainer.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('show');
    });

    window.setTimeout(() => {
        toast.classList.remove('show');
        window.setTimeout(() => toast.remove(), 200);
    }, duration);
};

export default function initToastSystem() {
    toastContainer = document.getElementById('toast-container');

    document.querySelectorAll('[data-ave-toast-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const type = trigger.getAttribute('data-ave-toast-trigger') || 'success';
            const message = trigger.getAttribute('data-ave-toast-message') || '';
            showToast(type, message);
        });
    });
}
