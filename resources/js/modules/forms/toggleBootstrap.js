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

        // Calculate max width of labels and set inline styles
        const onWidth = toggleOn.offsetWidth;
        const offWidth = toggleOff.offsetWidth;
        const maxWidth = Math.max(onWidth, offWidth);

        // Set equal width for both labels and container
        toggle.style.minWidth = (maxWidth + 5) + 'px';
        toggleOn.style.width = maxWidth + 'px';
        toggleOn.style.minWidth = maxWidth + 'px';
        toggleOff.style.width = maxWidth + 'px';
        toggleOff.style.minWidth = maxWidth + 'px';

        // Handle checkbox change
        checkbox.addEventListener('change', updateState);

        // Handle toggle click - just toggle state
        toggle.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });
    });
}
