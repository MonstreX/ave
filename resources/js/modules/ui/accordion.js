export default function initAccordion() {
    document.querySelectorAll('[data-ave-accordion-toggle]').forEach((toggle) => {
        const targetSelector = toggle.getAttribute('data-ave-accordion-toggle');
        if (!targetSelector) {
            return;
        }

        const selector = targetSelector.startsWith('#')
            ? targetSelector
            : `#${targetSelector}`;
        const body = document.querySelector(selector);

        if (!body) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            const newState = !isExpanded;

            toggle.setAttribute('aria-expanded', String(newState));
            body.classList.toggle('show', newState);
        });
    });
}


