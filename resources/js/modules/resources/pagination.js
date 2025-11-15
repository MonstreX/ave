/**
 * Pagination functionality: per-page selector and jump to page
 */

export default function initPagination() {
    initPerPageSelector();
    initJumpToPage();
}

/**
 * Per-page selector: update query string and reload
 */
function initPerPageSelector() {
    const selectors = document.querySelectorAll('.per-page-select');

    selectors.forEach(select => {
        select.addEventListener('change', function() {
            const perPage = parseInt(this.value, 10);
            if (!perPage || perPage < 1) {
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage.toString());
            url.searchParams.delete('page'); // reset pagination

            window.location.href = url.toString();
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
