import { showToast } from '../ui/toast.js';
import { trans } from '../../utils/translations.js';

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';
let handlersBound = false;

export default function initInlineEditing() {
    bindInlineHandlers();

    if (window.Ave?.events && !window.Ave.inlineEditingBound) {
        window.Ave.events.on('dom:updated', bindInlineHandlers);
        window.Ave.inlineEditingBound = true;
    }
}

function bindInlineHandlers() {
    if (handlersBound) {
        return;
    }

    document.addEventListener('click', handleToggleClick, true);
    handlersBound = true;
}

function handleToggleClick(event) {
    const trigger = event.target.closest('[data-ave-inline-toggle]');
    if (!trigger) {
        return;
    }

    event.preventDefault();

    if (trigger.dataset.loading === 'true') {
        return;
    }

    const endpoint = trigger.dataset.endpoint;
    const field = trigger.dataset.field;

    if (!endpoint || !field) {
        console.warn('Inline toggle missing endpoint or field', trigger);
        return;
    }

    const trueValue = trigger.dataset.trueValue ?? '1';
    const falseValue = trigger.dataset.falseValue ?? '0';
    const currentValue = trigger.dataset.currentValue ?? falseValue;
    const nextValue = currentValue === String(trueValue) ? falseValue : trueValue;

    toggleLoading(trigger, true);

    submitInline(endpoint, field, nextValue)
        .then((response) => {
            const canonical = response.canonical ?? response.value;
            const isOn = String(canonical) === String(trueValue);

            trigger.dataset.currentValue = String(canonical);
            trigger.classList.toggle('is-on', isOn);
            trigger.classList.toggle('is-off', !isOn);

            const dot = trigger.querySelector('.toggle-dot');
            const color = isOn ? (trigger.dataset.onColor || '#16a34a') : (trigger.dataset.offColor || '#d1d5db');
            if (dot) {
                dot.style.backgroundColor = color;
            }

            showToast('success', trans('inline.saved'));
        })
        .catch((error) => {
            console.error('Inline toggle failed', error);
            showToast('danger', error.message || trans('inline.error'));
        })
        .finally(() => toggleLoading(trigger, false));
}

async function submitInline(endpoint, field, value) {
    const response = await fetch(endpoint, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ field, value }),
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || trans('common.error'));
    }

    return response.json();
}

function toggleLoading(trigger, state) {
    trigger.dataset.loading = state ? 'true' : 'false';
    trigger.classList.toggle('is-loading', state);
    trigger.setAttribute('aria-busy', state ? 'true' : 'false');
}
