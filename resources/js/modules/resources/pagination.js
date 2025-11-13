/**
 * Pagination functionality: per-page selector and jump to page
 */

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

export default function initPagination() {
    initPerPageSelector();
    initJumpToPage();
}

/**
 * Per-page selector: save to session and reload
 */
function initPerPageSelector() {
    const selectors = document.querySelectorAll('.per-page-select');

    selectors.forEach(select => {
        select.addEventListener('change', async function() {
            const perPage = parseInt(this.value, 10);
            const endpoint = this.dataset.endpoint;

            if (!endpoint) {
                console.error('Per-page selector missing data-endpoint');
                return;
            }

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ per_page: perPage }),
                });

                if (!response.ok) {
                    throw new Error('Failed to save per-page preference');
                }

                // Reload page to apply new per-page setting
                window.location.reload();
            } catch (error) {
                console.error('Error saving per-page preference:', error);
                // Reload anyway - preference might be in query parameter
                window.location.reload();
            }
        });
    });
}

/**
 * Jump to page: navigate on Enter key
 */
function initJumpToPage() {
    const jumpInputs = document.querySelectorAll('.pagination-jump-input');

    jumpInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                jumpToPage(this);
            }
        });

        // Also handle blur event for better UX
        input.addEventListener('blur', function() {
            // Reset to current page if invalid
            const currentPage = getCurrentPageFromUrl();
            if (this.value === '' || parseInt(this.value, 10) < 1) {
                this.value = currentPage;
            }
        });
    });
}

function jumpToPage(input) {
    const targetPage = parseInt(input.value, 10);
    const lastPage = parseInt(input.dataset.lastPage, 10);
    const baseUrl = input.dataset.baseUrl;

    if (!targetPage || targetPage < 1 || targetPage > lastPage) {
        input.value = getCurrentPageFromUrl();
        return;
    }

    if (targetPage === getCurrentPageFromUrl()) {
        return; // Already on this page
    }

    // Build URL with page parameter
    const url = new URL(window.location.href);

    // Parse baseUrl to get path without query
    const baseUrlObj = new URL(baseUrl, window.location.origin);

    // Set page parameter
    if (targetPage === 1) {
        url.searchParams.delete('page');
    } else {
        url.searchParams.set('page', targetPage);
    }

    window.location.href = url.toString();
}

function getCurrentPageFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return parseInt(urlParams.get('page') || '1', 10);
}
