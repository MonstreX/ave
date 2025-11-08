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
        const toggleGroup = toggle.querySelector('.toggle-group');
        const toggleOn = toggle.querySelector('.toggle-on');
        const toggleOff = toggle.querySelector('.toggle-off');
        const handle = toggle.querySelector('.toggle-handle');

        if (!checkbox || !toggleGroup || !toggleOn || !toggleOff || !handle) return;

        const updateState = () => {
            toggle.classList.toggle('off', !checkbox.checked);
            toggleOn.classList.toggle('active', checkbox.checked);
            toggleOff.classList.toggle('active', !checkbox.checked);
        };

        // Инициализация состояния
        updateState();

        // Обработчик изменения checkbox
        checkbox.addEventListener('change', updateState);

        // Обработчик клика по всему toggle контейнеру (в том числе по toggle-group)
        toggle.addEventListener('click', e => {
            // Не обрабатываем клик по самому checkbox
            if (e.target === checkbox) return;

            e.preventDefault();
            e.stopPropagation();

            // Определяем где был клик и переключаем соответственно
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
