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

        // Инициализация состояния
        updateState();

        // Обработчик изменения checkbox
        checkbox.addEventListener('change', updateState);

        // Обработчик клика по toggle контейнеру - просто переключаем
        toggle.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });
    });
}
