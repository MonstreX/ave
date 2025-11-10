import { aveEvents } from '../../core/EventBus';

export default function initTabs() {
    const tabNavigations = document.querySelectorAll('[data-ave-tabs="nav"]');

    tabNavigations.forEach((nav) => {
        const tabsRoot = nav.closest('[data-ave-tabs-root]');
        const panes = tabsRoot
            ? Array.from(tabsRoot.querySelectorAll('[data-ave-tab-pane]'))
            : [];
        const items = Array.from(nav.querySelectorAll('[data-ave-tab-target]'));

        if (items.length === 0 || panes.length === 0) {
            return;
        }

        // Activate tab with validation errors on page load
        activateTabWithError(items, panes, tabsRoot);

        items.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();

                const targetSelector = item.getAttribute('data-ave-tab-target');
                if (!targetSelector) {
                    return;
                }

                const targetPane = panes.find((pane) =>
                    pane.matches(targetSelector)
                );

                if (!targetPane) {
                    return;
                }

                activateTab(items, panes, item, targetPane, tabsRoot);
            });
        });
    });
}

/**
 * Activate specific tab and emit event for components to reinitialize
 */
function activateTab(items, panes, activeItem, activePane, tabsRoot) {
    items.forEach((otherItem) => {
        otherItem.classList.toggle('active', otherItem === activeItem);
        const link = otherItem.querySelector('a');
        if (link) {
            link.setAttribute(
                'aria-selected',
                String(otherItem === activeItem)
            );
        }
    });

    panes.forEach((pane) => {
        pane.classList.toggle('active', pane === activePane);
    });

    // Emit event to notify components that a tab became visible
    // Components can use this to reinitialize (e.g., recalculate dimensions)
    aveEvents.emit('tab:activated', {
        pane: activePane,
        tabsRoot: tabsRoot
    });
}

/**
 * Find and activate first tab containing validation errors
 */
function activateTabWithError(items, panes, tabsRoot) {
    // Find first field with validation error
    const firstError = document.querySelector('.form-field.has-error');

    if (!firstError) {
        return; // No errors, do nothing
    }

    // Find which tab pane contains this error
    const errorPane = panes.find(pane => pane.contains(firstError));

    if (!errorPane) {
        return; // Error not in any tab (shouldn't happen)
    }

    // Find corresponding tab item
    const paneId = errorPane.getAttribute('id');
    const tabItem = items.find(item => {
        const targetSelector = item.getAttribute('data-ave-tab-target');
        return targetSelector === `#${paneId}`;
    });

    if (tabItem) {
        // Activate the tab with error
        activateTab(items, panes, tabItem, errorPane, tabsRoot);
    }
}

