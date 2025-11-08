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
        const handle = toggle.querySelector('.toggle-handle');

        if (!checkbox || !toggleOn || !toggleOff || !handle) return;

        const updateState = () => {
            toggle.classList.toggle('off', !checkbox.checked);
            toggleOn.classList.toggle('active', checkbox.checked);
            toggleOff.classList.toggle('active', !checkbox.checked);
        };

        // Инициализация состояния
        updateState();

        // Обработчик изменения checkbox
        checkbox.addEventListener('change', updateState);

        // Обработчик клика по "On" лейблу
        toggleOn.addEventListener('click', e => {
            e.preventDefault();
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        });

        // Обработчик клика по "Off" лейблу
        toggleOff.addEventListener('click', e => {
            e.preventDefault();
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change'));
        });

        // Обработчик клика по handle'у (переключение)
        handle.addEventListener('click', e => {
            e.preventDefault();
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });
    });
}
