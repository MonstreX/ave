/**
 * Initialize Tags Field
 *
 * Handles chip/tag input with add/remove functionality
 * @param {HTMLElement|Document} root - Container to search for tags fields
 */
export default function initTags(root = document) {
    const tagFields = root.querySelectorAll('[data-tags-field]');

    tagFields.forEach(chipInput => {
        const fieldName = chipInput.dataset.tagsField;
        const input = chipInput.querySelector('.tags-input');
        const hiddenInput = chipInput.parentElement.querySelector('.tags-hidden-input');
        const separator = input?.dataset.tagsSeparator || ',';

        if (!input) return;

        // Add tag on Enter or separator character
        // Handle TAB key properly
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === separator) {
                e.preventDefault();
                const tagText = input.value.trim();

                if (tagText) {
                    addTag(chipInput, tagText, hiddenInput, separator);
                    input.value = '';
                }
            }

            // Handle TAB key
            if (e.key === 'Tab') {
                const tagText = input.value.trim();

                // If there's text in input, add it as tag and prevent default
                if (tagText) {
                    e.preventDefault();
                    addTag(chipInput, tagText, hiddenInput, separator);
                    input.value = '';
                }
                // If input is empty, allow default TAB behavior (move to next field)
            }
        });

        // Add tag on blur if input has content
        input.addEventListener('blur', () => {
            const tagText = input.value.trim();
            if (tagText) {
                addTag(chipInput, tagText, hiddenInput, separator);
                input.value = '';
            }
        });

        // Remove tag on click
        chipInput.addEventListener('click', (e) => {
            if (e.target.classList.contains('chip-remove')) {
                const chip = e.target.closest('.chip');
                chip.remove();
                updateHiddenInput(chipInput, hiddenInput, separator);
            }
        });
    });
}

/**
 * Add a tag to the chip input
 * @param {HTMLElement} chipInput - The chip input container
 * @param {string} tagText - The tag text to add
 * @param {HTMLInputElement} hiddenInput - Hidden input to store values
 * @param {string} separator - Tag separator character
 */
function addTag(chipInput, tagText, hiddenInput, separator) {
    // Check for duplicates
    const existingChips = chipInput.querySelectorAll('.chip-text');
    const tagLower = tagText.toLowerCase();

    for (const chip of existingChips) {
        if (chip.textContent.toLowerCase() === tagLower) {
            return; // Duplicate, don't add
        }
    }

    // Create chip element
    const chip = document.createElement('div');
    chip.className = 'chip';
    chip.tabIndex = 0; // Make chip focusable for keyboard navigation
    chip.innerHTML = `
        <span class="chip-text">${escapeHtml(tagText)}</span>
        <span class="chip-remove" data-tag-remove>&times;</span>
    `;

    // Insert before input
    const input = chipInput.querySelector('.tags-input');
    input.parentElement.insertBefore(chip, input);

    // Update hidden input
    updateHiddenInput(chipInput, hiddenInput, separator);
}

/**
 * Update hidden input with current tags
 * @param {HTMLElement} chipInput - The chip input container
 * @param {HTMLInputElement} hiddenInput - Hidden input to update
 * @param {string} separator - Tag separator character
 */
function updateHiddenInput(chipInput, hiddenInput, separator) {
    const chips = chipInput.querySelectorAll('.chip-text');
    const tags = Array.from(chips).map(chip => chip.textContent.trim());
    hiddenInput.value = tags.join(separator.trim() + ' ');
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, (char) => map[char]);
}
