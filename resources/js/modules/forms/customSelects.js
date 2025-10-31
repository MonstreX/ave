let selectDropdowns = [];
let handlersBound = false;

const closeAllDropdowns = (exception) => {
    selectDropdowns.forEach((dropdown) => {
        if (dropdown !== exception) {
            dropdown.classList.remove('is-open');
        }
    });
};

const initMultiDropdown = (dropdown, toggle, labelNode, placeholder, options) => {
    const updateLabel = () => {
        const selected = options.filter((option) => option.querySelector('input')?.checked);
        if (!selected.length) {
            labelNode.textContent = placeholder;
            labelNode.classList.add('select-dropdown__placeholder');
            return;
        }

        const labels = selected.map((option) => option.getAttribute('data-label') || option.textContent.trim());
        const summary = labels.length > 3 ? `${labels.length} options selected` : labels.join(', ');
        labelNode.textContent = summary;
        labelNode.classList.remove('select-dropdown__placeholder');
    };

    options.forEach((option) => {
        const input = option.querySelector('input[type="checkbox"]');
        if (!input) {
            return;
        }

        option.classList.toggle('is-selected', input.checked);

        input.addEventListener('change', () => {
            option.classList.toggle('is-selected', input.checked);
            updateLabel();
        });

        option.addEventListener('click', (event) => {
            if (event.target === input) {
                return;
            }

            input.checked = !input.checked;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    updateLabel();

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        const willOpen = !dropdown.classList.contains('is-open');
        closeAllDropdowns(dropdown);
        dropdown.classList.toggle('is-open', willOpen);
    });
};

const initSingleDropdown = (dropdown, toggle, labelNode, placeholder, options) => {
    const updateSelection = () => {
        const selectedOption = options.find((option) => option.querySelector('input')?.checked);
        if (!selectedOption) {
            labelNode.textContent = placeholder;
            labelNode.classList.add('select-dropdown__placeholder');
        } else {
            labelNode.textContent = selectedOption.getAttribute('data-label') || selectedOption.textContent.trim();
            labelNode.classList.remove('select-dropdown__placeholder');
        }

        options.forEach((option) => {
            const input = option.querySelector('input');
            option.classList.toggle('is-selected', Boolean(input?.checked));
        });
    };

    options.forEach((option) => {
        const input = option.querySelector('input[type="radio"]');
        if (!input) {
            return;
        }

        option.classList.toggle('is-selected', input.checked);

        const selectOption = () => {
            if (input.checked) {
                updateSelection();
                dropdown.classList.remove('is-open');
            }
        };

        input.addEventListener('change', selectOption);
        option.addEventListener('click', (event) => {
            if (event.target === input) {
                return;
            }

            input.checked = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    updateSelection();

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        const willOpen = !dropdown.classList.contains('is-open');
        closeAllDropdowns(dropdown);
        dropdown.classList.toggle('is-open', willOpen);
    });
};

const initSearchBehaviour = (dropdown, options) => {
    const searchInput = dropdown.querySelector('[data-ave-select-dropdown-search]');
    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        options.forEach((option) => {
            const label = option.getAttribute('data-label') || option.textContent || '';
            const isMatch = label.toLowerCase().includes(query);
            option.classList.toggle('is-hidden', Boolean(query) && !isMatch);
        });
    });
};

export default function initCustomSelects(root = document) {
    const newDropdowns = Array.from(root.querySelectorAll('[data-ave-select-dropdown]'));

    if (!newDropdowns.length) {
        return;
    }

    // Add new dropdowns to global list (avoiding duplicates)
    newDropdowns.forEach(dd => {
        if (!selectDropdowns.includes(dd)) {
            selectDropdowns.push(dd);
        }
    });

    newDropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-ave-select-toggle]');
        const labelNode = dropdown.querySelector('[data-ave-select-label]');
        const menu = dropdown.querySelector('[data-ave-select-menu]');

        if (!toggle || !labelNode || !menu) {
            return;
        }

        const placeholder = toggle.getAttribute('data-placeholder') || labelNode.textContent.trim();
        labelNode.classList.add('select-dropdown__placeholder');
        const options = Array.from(menu.querySelectorAll('[data-ave-select-option]'));
        const type = dropdown.getAttribute('data-ave-select-dropdown') || 'single';

        if (type === 'multi') {
            initMultiDropdown(dropdown, toggle, labelNode, placeholder, options);
        } else {
            initSingleDropdown(dropdown, toggle, labelNode, placeholder, options);
        }

        if (type === 'search') {
            initSearchBehaviour(dropdown, options);
        }
    });

    if (!handlersBound) {
        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-ave-select-dropdown]')) {
                closeAllDropdowns();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllDropdowns();
            }
        });

        handlersBound = true;
    }
}
