export default function initOptionCards(root = document) {
    root.querySelectorAll('[data-ave-option-cards]').forEach((group) => {
        const cards = group.querySelectorAll('.option-card');

        const updateState = () => {
            cards.forEach((card) => {
                const input = card.querySelector('input');
                card.classList.toggle('selected', Boolean(input?.checked));
            });
        };

        cards.forEach((card) => {
            const input = card.querySelector('input');
            if (!input) {
                return;
            }

            input.addEventListener('change', () => {
                if (input.type === 'radio') {
                    cards.forEach((otherCard) => {
                        const otherInput = otherCard.querySelector('input');
                        otherCard.classList.toggle('selected', otherInput === input);
                    });
                    return;
                }

                card.classList.toggle('selected', input.checked);
            });

            card.addEventListener('click', (event) => {
                if (event.target === input) {
                    return;
                }

                if (input.type === 'radio') {
                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    input.checked = !input.checked;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });

        updateState();
    });
}


