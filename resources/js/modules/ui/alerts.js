export default function initAlerts() {
    document.querySelectorAll('.alert-dismissible .close').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const alert = button.closest('.alert');
            if (!alert) {
                return;
            }
            alert.remove();
        });
    });
}
