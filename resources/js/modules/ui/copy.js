import { showToast } from './toast';
import { trans } from '../../utils/translations.js';

const fallbackCopy = (text) => {
    const tempInput = document.createElement('textarea');
    tempInput.value = text;
    tempInput.style.position = 'fixed';
    tempInput.style.opacity = '0';
    document.body.appendChild(tempInput);
    tempInput.focus();
    tempInput.select();
    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (error) {
        copied = false;
    }
    document.body.removeChild(tempInput);
    return copied;
};

export default function initCopyToClipboard() {
    document.querySelectorAll('[data-ave-copy]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            const targetSelector = button.getAttribute('data-ave-copy');
            if (!targetSelector) {
                return;
            }

            const target = document.querySelector(targetSelector);
            if (!target) {
                return;
            }

            const value = target.value ?? target.textContent ?? '';

            try {
                await navigator.clipboard.writeText(value);
                    showToast('success', trans('common.copied_to_clipboard'));
            } catch (error) {
                const copied = fallbackCopy(value);
                if (copied) {
                    showToast('success', trans('common.copied_to_clipboard'));
                } else {
                    showToast('danger', trans('common.copy_failed'));
                }
            }
        });
    });
}



