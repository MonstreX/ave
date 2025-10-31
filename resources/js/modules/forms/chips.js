export default function initChipInputs(root = document) {
    root.querySelectorAll('[data-ave-chips]').forEach((container) => {
        const input = container.querySelector('input');

        container.addEventListener('click', () => {
            input?.focus();
        });

        container.addEventListener('keydown', (event) => {
            if (!input || event.key !== 'Enter') {
                return;
            }

            const value = input.value.trim();
            if (!value) {
                return;
            }

            event.preventDefault();
            const chip = document.createElement('span');
            chip.className = 'chip';
            chip.innerHTML = `${value} <span class="chip-remove" data-ave-chip-remove>&times;</span>`;
            container.insertBefore(chip, input);
            input.value = '';
        });

        container.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-ave-chip-remove]');
            if (!remove) {
                return;
            }
            event.preventDefault();
            const chip = remove.closest('.chip');
            chip?.remove();
        });
    });
}



