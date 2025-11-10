import { aveEvents } from '../../core/EventBus';

/**
 * Initialize Bootstrap Toggle components
 * @param {HTMLElement|Document} root - Container to search for toggle elements
 */
export default function initToggleBootstrap(root = document) {
    const toggles = root.querySelectorAll('.toggle[data-toggle="toggle"]');

    toggles.forEach(toggle => {
        // Защита от двойной инициализации
        if (toggle.dataset.initialized === 'true') return;
        toggle.dataset.initialized = 'true';

        const checkbox = toggle.querySelector('input[type="checkbox"]');
        const toggleOn = toggle.querySelector('.toggle-on');
        const toggleOff = toggle.querySelector('.toggle-off');

        if (!checkbox || !toggleOn || !toggleOff) return;

        const updateState = () => {
            toggle.classList.toggle('off', !checkbox.checked);
            toggleOn.classList.toggle('active', checkbox.checked);
            toggleOff.classList.toggle('active', !checkbox.checked);
        };

        // Initialize state
        updateState();

        // Calculate and set widths
        recalculateToggleWidth(toggle);

        // Handle checkbox change
        checkbox.addEventListener('change', updateState);

        // Handle toggle click - just toggle state
        toggle.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });
    });

    // Subscribe to tab activation event to recalculate widths
    subscribeToTabActivation();
}

/**
 * Recalculate and update toggle width
 * Used when toggle becomes visible (e.g., in tabs)
 */
export function recalculateToggleWidth(toggle) {
    const toggleOn = toggle.querySelector('.toggle-on');
    const toggleOff = toggle.querySelector('.toggle-off');

    if (!toggleOn || !toggleOff) return;

    // Calculate max width of labels and set inline styles
    const onWidth = toggleOn.offsetWidth;
    const offWidth = toggleOff.offsetWidth;
    const maxWidth = Math.max(onWidth, offWidth);

    // Only update if we got valid widths (not 0)
    if (maxWidth > 0) {
        toggle.style.minWidth = (maxWidth + 5) + 'px';
        toggleOn.style.width = maxWidth + 'px';
        toggleOn.style.minWidth = maxWidth + 'px';
        toggleOff.style.width = maxWidth + 'px';
        toggleOff.style.minWidth = maxWidth + 'px';
    }
}

/**
 * Subscribe to tab:activated event and recalculate widths
 * This provides a decoupled way for toggle to respond to tab changes
 */
let tabActivationListenerAttached = false;

function subscribeToTabActivation() {
    // Only attach listener once globally
    if (tabActivationListenerAttached) return;
    tabActivationListenerAttached = true;

    aveEvents.on('tab:activated', ({ pane }) => {
        // When a tab is activated, recalculate widths for all toggles in that pane
        requestAnimationFrame(() => {
            const toggles = pane.querySelectorAll('.toggle[data-toggle="toggle"]');
            toggles.forEach(toggle => {
                recalculateToggleWidth(toggle);
            });
        });
    });
}

