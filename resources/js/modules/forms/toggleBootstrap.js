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

        // Обработчик клика по всему toggle контейнеру
        toggle.addEventListener('click', (e) => {
            // Пропускаем если клик на самом checkbox
            if (e.target === checkbox) return;

            // Определяем позицию клика в контейнере
            const rect = toggle.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const toggleWidth = rect.width;

            // Если клик в левой половине - ON, если в правой - OFF
            if (clickX < toggleWidth / 2) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }

            checkbox.dispatchEvent(new Event('change'));
        });
    });
}
