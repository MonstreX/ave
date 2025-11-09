/**
 * Slug Field - Client-side AJAX slug generation
 *
 * Handles automatic slug generation from source field via AJAX requests
 * to the server. Shows loading spinner while request is in progress.
 *
 * Usage in Blade:
 *   <input data-slug-field
 *          data-slug-source="title"
 *          data-slug-separator="-"
 *          data-slug-locale="ru"
 *          data-slug-api-url="/admin/ave/api/slug" />
 *
 * Behavior:
 * - Focus on empty slug field → request slug generation from server
 * - Focus on non-empty slug field → do nothing (user manual edit)
 * - Show spinner while request is in progress
 * - Support for aborting previous requests
 */

/**
 * Show loading spinner in slug field
 */
function showLoader(slugInput) {
    const wrapper = slugInput.closest('.slug-input-wrapper');
    const loader = wrapper?.querySelector('.slug-loader');
    if (loader) {
        loader.style.display = 'block';
    }
    slugInput.classList.add('is-loading');
}

/**
 * Hide loading spinner in slug field
 */
function hideLoader(slugInput) {
    const wrapper = slugInput.closest('.slug-input-wrapper');
    const loader = wrapper?.querySelector('.slug-loader');
    if (loader) {
        loader.style.display = 'none';
    }
    slugInput.classList.remove('is-loading');
}

/**
 * Request slug generation from server via AJAX
 *
 * @param {string} text - Source text to generate slug from
 * @param {string} separator - Slug separator character (e.g., '-')
 * @param {string|null} locale - Language locale for transliteration (e.g., 'ru')
 * @param {string} apiUrl - API endpoint URL for slug generation
 * @returns {Promise<string|null>} Generated slug or null on error
 */
async function generateSlugFromServer(text, separator, locale, apiUrl) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                text: text,
                separator: separator,
                locale: locale,
            }),
        });

        if (!response.ok) {
            console.error(`Slug API returned status ${response.status}`);
            return null;
        }

        const data = await response.json();
        return data.slug || null;
    } catch (error) {
        console.error('Failed to generate slug:', error);
        return null;
    }
}

/**
 * Initialize a single slug field
 *
 * @param {HTMLInputElement} slugInput - The slug input element
 */
function initSlugField(slugInput) {
    const sourceFieldName = slugInput.dataset.slugSource;
    if (!sourceFieldName) {
        console.warn('Slug field missing data-slug-source:', slugInput);
        return;
    }

    const sourceField = document.querySelector(`[name="${sourceFieldName}"]`);
    if (!sourceField) {
        console.warn(`Source field not found: ${sourceFieldName}`);
        return;
    }

    const separator = slugInput.dataset.slugSeparator || '-';
    const locale = slugInput.dataset.slugLocale || null;
    const apiUrl = slugInput.dataset.slugApiUrl;

    if (!apiUrl) {
        console.error('Slug field missing data-slug-api-url');
        return;
    }

    // Track current request to allow cancellation
    let currentRequest = null;

    /**
     * Focus handler: trigger slug generation if field is empty
     */
    slugInput.addEventListener('focus', async function() {
        const currentValue = this.value.trim();

        // Only generate if field is empty
        if (!currentValue) {
            const sourceValue = sourceField.value.trim();

            if (sourceValue) {
                // Cancel previous request if any
                if (currentRequest) {
                    currentRequest.abort();
                    currentRequest = null;
                }

                // Show loader
                showLoader(this);

                // Create new abort controller for this request
                const controller = new AbortController();
                currentRequest = controller;

                try {
                    const slug = await generateSlugFromServer(
                        sourceValue,
                        separator,
                        locale,
                        apiUrl
                    );

                    // Only update if this is still the current request
                    if (slug !== null && currentRequest === controller) {
                        this.value = slug;
                    }
                } finally {
                    // Hide loader and cleanup
                    hideLoader(this);
                    if (currentRequest === controller) {
                        currentRequest = null;
                    }
                }
            }
        }
        // If field already has value, don't modify (manual edit protection)
    });
}

/**
 * Auto-initialize all slug fields in the document
 *
 * @param {HTMLElement} root - Root element to search for slug fields (default: document)
 */
export default function initSlugFields(root = document) {
    const slugInputs = root.querySelectorAll('[data-slug-field]');
    slugInputs.forEach(initSlugField);
}

/**
 * Export for direct use if needed
 */
export { generateSlugFromServer };


